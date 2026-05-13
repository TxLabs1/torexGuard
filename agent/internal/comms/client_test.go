package comms

import (
	"net/http"
	"net/http/httptest"
	"os"
	"path/filepath"
	"strings"
	"testing"
)

func TestLoadConfig_DefaultsAndFlags(t *testing.T) {
	cfg, err := LoadConfig("/nonexistent.yaml", "tg_key", "https://example.test")
	if err != nil {
		t.Fatalf("unexpected error: %v", err)
	}
	if cfg.APIKey != "tg_key" {
		t.Errorf("api_key not propagated: %s", cfg.APIKey)
	}
	if cfg.Host != "https://example.test" {
		t.Errorf("host not propagated: %s", cfg.Host)
	}
	if cfg.Heartbeat == 0 || cfg.ScanInterval == 0 {
		t.Errorf("defaults not seeded: heartbeat=%d scan=%d", cfg.Heartbeat, cfg.ScanInterval)
	}
}

func TestLoadConfig_ParsesYaml(t *testing.T) {
	dir := t.TempDir()
	path := filepath.Join(dir, "config.yaml")
	yaml := `api_key: tg_yaml
host: https://yaml.test
scan_interval: 120
heartbeat_interval: 30
monitored_paths:
  - /opt/app
  - /srv/code
auto_quarantine: true
`
	if err := os.WriteFile(path, []byte(yaml), 0o600); err != nil {
		t.Fatal(err)
	}
	cfg, err := LoadConfig(path, "", "")
	if err != nil {
		t.Fatalf("unexpected error: %v", err)
	}
	if cfg.Host != "https://yaml.test" {
		t.Errorf("host not loaded: %q", cfg.Host)
	}
	if cfg.ScanInterval != 120 {
		t.Errorf("scan_interval not loaded: %d", cfg.ScanInterval)
	}
	if !cfg.AutoQuarantine {
		t.Errorf("auto_quarantine not loaded")
	}
	if len(cfg.MonitoredPaths) != 2 {
		t.Errorf("monitored_paths not loaded: %#v", cfg.MonitoredPaths)
	}
}

func TestPostWithRetry_RetriesOn5xx(t *testing.T) {
	attempts := 0
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		attempts++
		if attempts < 3 {
			http.Error(w, "boom", http.StatusBadGateway)
			return
		}
		_, _ = w.Write([]byte(`{"ok":true}`))
	}))
	defer srv.Close()

	c := NewClient(&Config{Host: srv.URL, AgentToken: "t"})
	body, err := c.post("/api/v1/agent/heartbeat", map[string]string{"hello": "world"})
	if err != nil {
		t.Fatalf("unexpected error after retries: %v", err)
	}
	if !strings.Contains(string(body), "ok") {
		t.Errorf("unexpected body: %s", string(body))
	}
	if attempts < 3 {
		t.Errorf("expected at least 3 attempts, got %d", attempts)
	}
}

func TestPostWithRetry_DoesNotRetryOn4xx(t *testing.T) {
	attempts := 0
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		attempts++
		http.Error(w, "bad token", http.StatusUnauthorized)
	}))
	defer srv.Close()

	c := NewClient(&Config{Host: srv.URL, AgentToken: "t"})
	if _, err := c.post("/api/v1/agent/heartbeat", map[string]string{}); err == nil {
		t.Fatal("expected error for 401")
	}
	if attempts != 1 {
		t.Errorf("expected 1 attempt for 4xx, got %d", attempts)
	}
}
