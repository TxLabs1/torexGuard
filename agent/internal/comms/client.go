// Package comms is the agent's HTTPS client to the Torex Guard control plane.
package comms

import (
	"bytes"
	"context"
	"crypto/tls"
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"log/slog"
	"math/rand"
	"net/http"
	"os"
	"runtime"
	"time"

	"github.com/shirou/gopsutil/v3/cpu"
	"github.com/shirou/gopsutil/v3/disk"
	"github.com/shirou/gopsutil/v3/mem"
	"gopkg.in/yaml.v3"
)

type Config struct {
	APIKey         string   `yaml:"api_key"`
	Host           string   `yaml:"host"`
	AgentToken     string   `yaml:"agent_token"`
	AgentID        string   `yaml:"agent_id"`
	MonitoredPaths []string `yaml:"monitored_paths"`
	ExcludedPaths  []string `yaml:"excluded_paths"`
	ScanInterval   int      `yaml:"scan_interval"`
	Heartbeat      int      `yaml:"heartbeat_interval"`
	AutoQuarantine bool     `yaml:"auto_quarantine"`
	InsecureTLS    bool     `yaml:"insecure_tls"` // for self-signed staging hosts
}

type AgentInfo struct {
	AgentID    string `json:"agent_id"`
	AgentToken string `json:"agent_token"`
	ServerID   int    `json:"server_id"`
	Config     struct {
		ScanInterval        int      `json:"scan_interval"`
		HeartbeatInterval   int      `json:"heartbeat_interval"`
		BatchSize           int      `json:"batch_size"`
		MonitoredPaths      []string `json:"monitored_paths"`
		ExcludedPaths       []string `json:"excluded_paths"`
		DangerousExtensions []string `json:"dangerous_extensions"`
		AutoQuarantine      bool     `json:"auto_quarantine"`
		RealtimeMonitoring  bool     `json:"realtime_monitoring"`
	} `json:"config"`
}

type Client struct {
	config     *Config
	httpClient *http.Client
}

func NewClient(cfg *Config) *Client {
	transport := &http.Transport{
		MaxIdleConns:        16,
		MaxIdleConnsPerHost: 4,
		IdleConnTimeout:     90 * time.Second,
		TLSClientConfig: &tls.Config{
			InsecureSkipVerify: cfg.InsecureTLS,
			MinVersion:         tls.VersionTLS12,
		},
	}
	return &Client{
		config:     cfg,
		httpClient: &http.Client{Timeout: 30 * time.Second, Transport: transport},
	}
}

// ── Registration ──────────────────────────────────────────────

func (c *Client) Register() (*AgentInfo, error) {
	hostname, _ := os.Hostname()

	cpuCount := runtime.NumCPU()
	memInfo, _ := mem.VirtualMemory()
	memTotal := int64(0)
	if memInfo != nil {
		memTotal = int64(memInfo.Total)
	}
	diskTotal := int64(0)
	if usage, err := disk.Usage("/"); err == nil {
		diskTotal = int64(usage.Total)
	}

	payload := map[string]interface{}{
		"project_api_key": c.config.APIKey,
		"hostname":        hostname,
		"os":              runtime.GOOS,
		"arch":            runtime.GOARCH,
		"cpu_count":       cpuCount,
		"memory_total":    memTotal,
		"disk_total":      diskTotal,
		"version":         "1.0.0",
		"capabilities":    []string{"file_scan", "process_monitor", "file_integrity", "network_monitor", "remote_commands"},
	}

	body, err := c.postWithRetry(context.Background(), "/api/v1/agent/register", payload, "" /* no token yet */)
	if err != nil {
		return nil, fmt.Errorf("register: %w", err)
	}

	var info AgentInfo
	if err := json.Unmarshal(body, &info); err != nil {
		return nil, fmt.Errorf("register: parse response: %w", err)
	}

	c.config.AgentToken = info.AgentToken
	c.config.AgentID = info.AgentID
	if info.Config.HeartbeatInterval > 0 {
		c.config.Heartbeat = info.Config.HeartbeatInterval
	}
	if info.Config.ScanInterval > 0 {
		c.config.ScanInterval = info.Config.ScanInterval
	}
	if len(info.Config.MonitoredPaths) > 0 {
		c.config.MonitoredPaths = info.Config.MonitoredPaths
	}
	if len(info.Config.ExcludedPaths) > 0 {
		c.config.ExcludedPaths = info.Config.ExcludedPaths
	}
	c.config.AutoQuarantine = info.Config.AutoQuarantine

	return &info, nil
}

// ── Heartbeat ─────────────────────────────────────────────────

func (c *Client) StartHeartbeat(stop chan struct{}) {
	defer func() {
		if r := recover(); r != nil {
			slog.Error("heartbeat panic", "recover", r)
		}
	}()

	interval := c.config.Heartbeat
	if interval == 0 {
		interval = 60
	}
	ticker := time.NewTicker(time.Duration(interval) * time.Second)
	defer ticker.Stop()

	c.sendHeartbeat()
	for {
		select {
		case <-ticker.C:
			c.sendHeartbeat()
		case <-stop:
			return
		}
	}
}

func (c *Client) sendHeartbeat() {
	payload := map[string]interface{}{
		"cpu_usage":    sampleCPU(),
		"memory_usage": sampleMemory(),
		"disk_usage":   sampleDisk(),
		"timestamp":    time.Now().Unix(),
	}
	if _, err := c.post("/api/v1/agent/heartbeat", payload); err != nil {
		slog.Warn("heartbeat failed", "err", err)
	}
}

// ── Reporting ─────────────────────────────────────────────────

func (c *Client) ReportThreat(threat interface{}) error {
	_, err := c.post("/api/v1/agent/threats", threat)
	return err
}

func (c *Client) ReportEvent(event interface{}) error {
	_, err := c.post("/api/v1/agent/events", event)
	return err
}

func (c *Client) SubmitSnapshot(files interface{}) error {
	_, err := c.post("/api/v1/agent/integrity/snapshot", map[string]interface{}{"files": files})
	return err
}

func (c *Client) ReportFileChanges(changes interface{}) error {
	_, err := c.post("/api/v1/agent/integrity/changes", map[string]interface{}{"changes": changes})
	return err
}

// ── Command channel ───────────────────────────────────────────

type PendingCommand struct {
	ID        int64  `json:"id"`
	Type      string `json:"type"`
	Target    string `json:"target"`
	ThreatID  *int64 `json:"threat_id,omitempty"`
	Automated bool   `json:"automated"`
}

type pendingResponse struct {
	Commands []PendingCommand `json:"commands"`
}

// FetchCommands polls /commands/pending. The server transitions returned
// rows to 'executing' so a redelivery does not happen.
func (c *Client) FetchCommands() ([]PendingCommand, error) {
	req, err := http.NewRequest(http.MethodGet, c.config.Host+"/api/v1/agent/commands/pending", nil)
	if err != nil {
		return nil, err
	}
	c.applyHeaders(req)

	resp, err := c.httpClient.Do(req)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()

	if resp.StatusCode >= 400 {
		b, _ := io.ReadAll(resp.Body)
		return nil, fmt.Errorf("commands/pending %d: %s", resp.StatusCode, string(b))
	}
	var pr pendingResponse
	if err := json.NewDecoder(resp.Body).Decode(&pr); err != nil {
		return nil, err
	}
	return pr.Commands, nil
}

// AckCommand reports the result of executing a single command.
func (c *Client) AckCommand(id int64, status, result, errMsg string) error {
	payload := map[string]interface{}{
		"status": status,
		"result": result,
		"error":  errMsg,
	}
	body, err := json.Marshal(payload)
	if err != nil {
		return err
	}
	url := fmt.Sprintf("%s/api/v1/agent/commands/%d", c.config.Host, id)
	req, err := http.NewRequest(http.MethodPatch, url, bytes.NewBuffer(body))
	if err != nil {
		return err
	}
	c.applyHeaders(req)
	req.Header.Set("Content-Type", "application/json")

	resp, err := c.httpClient.Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()

	if resp.StatusCode >= 400 {
		b, _ := io.ReadAll(resp.Body)
		return fmt.Errorf("commands/%d %d: %s", id, resp.StatusCode, string(b))
	}
	return nil
}

func (c *Client) Disconnect() {
	_, _ = c.post("/api/v1/agent/disconnect", map[string]string{})
}

// ── Low-level HTTP ────────────────────────────────────────────

func (c *Client) post(endpoint string, payload interface{}) ([]byte, error) {
	return c.postWithRetry(context.Background(), endpoint, payload, c.config.AgentToken)
}

// postWithRetry performs up to 4 attempts with full jitter exponential backoff.
// Retries on network errors and 5xx; gives up immediately on 4xx.
func (c *Client) postWithRetry(ctx context.Context, endpoint string, payload interface{}, token string) ([]byte, error) {
	body, err := json.Marshal(payload)
	if err != nil {
		return nil, err
	}

	const maxAttempts = 4
	backoff := 500 * time.Millisecond
	var lastErr error

	for attempt := 1; attempt <= maxAttempts; attempt++ {
		req, err := http.NewRequestWithContext(ctx, http.MethodPost, c.config.Host+endpoint, bytes.NewBuffer(body))
		if err != nil {
			return nil, err
		}
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("Accept", "application/json")
		req.Header.Set("User-Agent", "TorexGuardAgent/1.0.0")
		if token != "" {
			req.Header.Set("X-Agent-Token", token)
		}

		resp, err := c.httpClient.Do(req)
		if err != nil {
			lastErr = err
			c.sleep(ctx, backoff)
			backoff *= 2
			continue
		}
		buf, _ := io.ReadAll(resp.Body)
		resp.Body.Close()

		if resp.StatusCode < 400 {
			return buf, nil
		}

		// 4xx: don't retry, the request is bad.
		if resp.StatusCode < 500 {
			return nil, fmt.Errorf("api %s %d: %s", endpoint, resp.StatusCode, string(buf))
		}

		lastErr = fmt.Errorf("api %s %d: %s", endpoint, resp.StatusCode, string(buf))
		c.sleep(ctx, backoff)
		backoff *= 2
	}

	if lastErr == nil {
		lastErr = errors.New("unknown error")
	}
	return nil, fmt.Errorf("after %d attempts: %w", maxAttempts, lastErr)
}

func (c *Client) sleep(ctx context.Context, d time.Duration) {
	jitter := time.Duration(rand.Int63n(int64(d) / 2))
	select {
	case <-ctx.Done():
	case <-time.After(d + jitter):
	}
}

func (c *Client) applyHeaders(req *http.Request) {
	req.Header.Set("Accept", "application/json")
	req.Header.Set("User-Agent", "TorexGuardAgent/1.0.0")
	if c.config.AgentToken != "" {
		req.Header.Set("X-Agent-Token", c.config.AgentToken)
	}
}

// ── Config loader ─────────────────────────────────────────────

// LoadConfig parses the YAML at `path` (optional) and merges the supplied
// flag overrides. Missing files are not an error: the function returns a
// usable default config seeded from the CLI flags.
func LoadConfig(path, apiKey, host string) (*Config, error) {
	cfg := &Config{
		APIKey:       apiKey,
		Host:         host,
		ScanInterval: 300,
		Heartbeat:    60,
		MonitoredPaths: []string{
			"/var/www",
			"/srv/www",
		},
		ExcludedPaths: []string{"/vendor", "/node_modules", "/.git"},
	}

	if path != "" {
		if data, err := os.ReadFile(path); err == nil {
			if err := yaml.Unmarshal(data, cfg); err != nil {
				return nil, fmt.Errorf("yaml parse %s: %w", path, err)
			}
		} else if !errors.Is(err, os.ErrNotExist) {
			return nil, fmt.Errorf("read %s: %w", path, err)
		}
	}

	// Flags override file values when explicitly set.
	if apiKey != "" {
		cfg.APIKey = apiKey
	}
	if host != "" {
		cfg.Host = host
	}

	if cfg.Host == "" {
		return nil, errors.New("host must be provided via --host or yaml")
	}
	if cfg.APIKey == "" && cfg.AgentToken == "" {
		return nil, errors.New("api_key required (only optional once an agent_token is persisted)")
	}
	return cfg, nil
}

// Persist writes the (possibly updated) config back to the yaml file. Used
// once after Register() so subsequent starts reuse the issued agent_token.
func (c *Config) Persist(path string) error {
	if path == "" {
		return nil
	}
	data, err := yaml.Marshal(c)
	if err != nil {
		return err
	}
	if err := os.MkdirAll(directoryOf(path), 0o755); err != nil {
		return err
	}
	return os.WriteFile(path, data, 0o600)
}

func directoryOf(p string) string {
	for i := len(p) - 1; i >= 0; i-- {
		if p[i] == '/' || p[i] == '\\' {
			return p[:i]
		}
	}
	return "."
}

// ── Metrics ───────────────────────────────────────────────────

func sampleCPU() float64 {
	pcts, err := cpu.Percent(200*time.Millisecond, false)
	if err != nil || len(pcts) == 0 {
		return 0
	}
	return roundTwo(pcts[0])
}

func sampleMemory() float64 {
	m, err := mem.VirtualMemory()
	if err != nil || m == nil {
		return 0
	}
	return roundTwo(m.UsedPercent)
}

func sampleDisk() float64 {
	u, err := disk.Usage("/")
	if err != nil || u == nil {
		return 0
	}
	return roundTwo(u.UsedPercent)
}

func roundTwo(v float64) float64 {
	return float64(int(v*100)) / 100
}
