// Command torex-agent is the Torex Guard endpoint agent.
//
// Lifecycle:
//
//  1. Load config (yaml file + flag overrides).
//  2. POST /api/v1/agent/register if we don't have a token yet (the response
//     contains the long-lived agent_token + initial config).
//  3. Persist the token back to the yaml file so subsequent boots reuse it.
//  4. Start: heartbeat, file integrity, process monitor, network monitor,
//     command executor, scheduled scans — each in its own goroutine.
//  5. Wait for SIGINT/SIGTERM, then cleanly drain.
//
// Build: go build -trimpath -ldflags='-s -w' -o torex-agent ./cmd
package main

import (
	"context"
	"flag"
	"fmt"
	"log/slog"
	"os"
	"os/signal"
	"syscall"
	"time"

	"github.com/torexlabs/guard-agent/internal/comms"
	"github.com/torexlabs/guard-agent/internal/executor"
	"github.com/torexlabs/guard-agent/internal/integrity"
	"github.com/torexlabs/guard-agent/internal/monitor"
	"github.com/torexlabs/guard-agent/internal/scanner"
)

// Version is overwritten at build time via -ldflags.
var Version = "1.0.0"

func main() {
	var (
		apiKey     = flag.String("api-key", os.Getenv("TOREX_API_KEY"), "project API key (only required on first run)")
		host       = flag.String("host", envOr("TOREX_HOST", "https://guard.torexlabs.cloud"), "control plane base URL")
		daemon     = flag.Bool("daemon", false, "run as background daemon")
		scanOnly   = flag.Bool("scan", false, "run one-time scan and exit")
		configPath = flag.String("config", envOr("TOREX_CONFIG", "/etc/torex-agent/config.yaml"), "config file path")
		insecure   = flag.Bool("insecure", false, "skip TLS verification (testing only)")
		logFormat  = flag.String("log-format", envOr("TOREX_LOG_FORMAT", "text"), "log format: text|json")
	)
	flag.Parse()

	setupLogger(*logFormat)
	slog.Info("torex agent starting", "version", Version, "host", *host)

	cfg, err := comms.LoadConfig(*configPath, *apiKey, *host)
	if err != nil {
		slog.Error("config load failed", "err", err)
		os.Exit(2)
	}
	if *insecure {
		cfg.InsecureTLS = true
	}

	client := comms.NewClient(cfg)

	// If we don't have a token yet, register. Otherwise pick up where we left off.
	if cfg.AgentToken == "" {
		info, err := client.Register()
		if err != nil {
			slog.Error("registration failed", "err", err)
			os.Exit(3)
		}
		slog.Info("registered", "agent_id", info.AgentID, "server_id", info.ServerID)
		if err := cfg.Persist(*configPath); err != nil {
			slog.Warn("could not persist config", "path", *configPath, "err", err)
		}
	}

	if *scanOnly {
		s := scanner.NewScanner(cfg, client)
		result := s.RunFullScan()
		fmt.Printf("scan complete files=%d threats=%d\n", result.FilesScanned, result.ThreatsFound)
		return
	}

	agent := NewAgent(cfg, client)
	agent.Start(*daemon)
}

func envOr(key, fallback string) string {
	if v := os.Getenv(key); v != "" {
		return v
	}
	return fallback
}

func setupLogger(format string) {
	var handler slog.Handler
	opts := &slog.HandlerOptions{Level: slog.LevelInfo}
	switch format {
	case "json":
		handler = slog.NewJSONHandler(os.Stdout, opts)
	default:
		handler = slog.NewTextHandler(os.Stdout, opts)
	}
	slog.SetDefault(slog.New(handler))
}

// ─────────────────────────────────────────────────────────────────
// Agent orchestrator
// ─────────────────────────────────────────────────────────────────

type Agent struct {
	config    *comms.Config
	client    *comms.Client
	scanner   *scanner.Scanner
	monitor   *monitor.Monitor
	integrity *integrity.IntegrityMonitor
	executor  *executor.Executor
	stopChan  chan struct{}
}

func NewAgent(cfg *comms.Config, client *comms.Client) *Agent {
	return &Agent{
		config:    cfg,
		client:    client,
		scanner:   scanner.NewScanner(cfg, client),
		monitor:   monitor.NewMonitor(cfg, client),
		integrity: integrity.NewIntegrityMonitor(cfg, client),
		executor:  executor.New(client, "/var/torex-guard/quarantine"),
		stopChan:  make(chan struct{}),
	}
}

func (a *Agent) Start(_ bool) {
	ctx, cancel := context.WithCancel(context.Background())
	defer cancel()

	slog.Info("agent active — monitoring started")

	go a.integrity.BuildBaseline()
	go a.monitor.WatchProcesses(a.stopChan)
	go a.monitor.WatchNetwork(a.stopChan)
	go a.integrity.Watch(a.stopChan)
	go a.client.StartHeartbeat(a.stopChan)
	go a.scanner.StartScheduled(a.stopChan)
	go a.executor.Run(ctx, a.stopChan, 10*time.Second)

	sigChan := make(chan os.Signal, 1)
	signal.Notify(sigChan, syscall.SIGINT, syscall.SIGTERM)

	sig := <-sigChan
	slog.Info("shutdown signal received", "signal", sig.String())

	close(a.stopChan)
	cancel()
	a.client.Disconnect()

	// brief grace period for in-flight goroutines.
	time.Sleep(500 * time.Millisecond)
	slog.Info("agent shutdown complete")
}
