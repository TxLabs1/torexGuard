// Package monitor performs runtime checks (processes, outbound connections)
// at fixed intervals and reports anything that matches the suspicious lists.
package monitor

import (
	"fmt"
	"log/slog"
	"os/exec"
	"strings"
	"time"

	"github.com/torexlabs/guard-agent/internal/comms"
)

type ProcessEvent struct {
	Type        string                 `json:"type"`
	Severity    string                 `json:"severity"`
	ProcessName string                 `json:"process_name"`
	PID         int                    `json:"pid"`
	Command     string                 `json:"command"`
	User        string                 `json:"user"`
	Payload     map[string]interface{} `json:"payload"`
	OccurredAt  time.Time              `json:"occurred_at"`
}

type NetworkEvent struct {
	Type        string                 `json:"type"`
	Severity    string                 `json:"severity"`
	Destination string                 `json:"destination"`
	Port        int                    `json:"port"`
	Protocol    string                 `json:"protocol"`
	ProcessName string                 `json:"process_name"`
	PID         int                    `json:"pid"`
	Payload     map[string]interface{} `json:"payload"`
	OccurredAt  time.Time              `json:"occurred_at"`
}

var suspiciousProcesses = []string{
	"xmrig", "minerd", "cpuminer", "nbminer",
	"netcat", "nc ", "ncat",
	"bash -i", "sh -i",
	"python -c", "python3 -c",
	"perl -e",
	"msfvenom", "meterpreter",
}

var suspiciousPorts = map[int]string{
	4444:  "metasploit",
	1337:  "backdoor",
	31337: "elite backdoor",
	6666:  "irc botnet",
	6667:  "irc botnet",
}

type Monitor struct {
	config *comms.Config
	client *comms.Client
}

func NewMonitor(cfg *comms.Config, client *comms.Client) *Monitor {
	return &Monitor{config: cfg, client: client}
}

func (m *Monitor) WatchProcesses(stop chan struct{}) {
	defer func() {
		if r := recover(); r != nil {
			slog.Error("monitor processes panic", "recover", r)
		}
	}()

	ticker := time.NewTicker(15 * time.Second)
	defer ticker.Stop()
	slog.Info("process monitoring started")

	for {
		select {
		case <-ticker.C:
			m.checkProcesses()
		case <-stop:
			return
		}
	}
}

func (m *Monitor) WatchNetwork(stop chan struct{}) {
	defer func() {
		if r := recover(); r != nil {
			slog.Error("monitor network panic", "recover", r)
		}
	}()

	ticker := time.NewTicker(30 * time.Second)
	defer ticker.Stop()
	slog.Info("network monitoring started")

	for {
		select {
		case <-ticker.C:
			m.checkConnections()
		case <-stop:
			return
		}
	}
}

func (m *Monitor) checkProcesses() {
	out, err := exec.Command("ps", "aux").Output()
	if err != nil {
		return
	}

	lines := strings.Split(string(out), "\n")
	for _, line := range lines[1:] {
		lower := strings.ToLower(line)
		for _, suspicious := range suspiciousProcesses {
			if !strings.Contains(lower, strings.ToLower(suspicious)) {
				continue
			}
			snippet := line
			if len(snippet) > 200 {
				snippet = snippet[:200]
			}
			slog.Warn("suspicious process detected", "match", suspicious, "process", snippet)

			event := map[string]interface{}{
				"type":        "suspicious_process",
				"severity":    "high",
				"payload":     map[string]interface{}{"process_line": line, "matched": suspicious},
				"occurred_at": time.Now(),
			}
			if err := m.client.ReportEvent(event); err != nil {
				slog.Warn("event report failed", "err", err)
			}
		}
	}
}

func (m *Monitor) checkConnections() {
	out, err := exec.Command("ss", "-tunp").Output()
	if err != nil {
		out, err = exec.Command("netstat", "-tunp").Output()
		if err != nil {
			return
		}
	}

	lines := strings.Split(string(out), "\n")
	for _, line := range lines {
		for port, portName := range suspiciousPorts {
			portStr := fmt.Sprintf(":%d", port)
			if !strings.Contains(line, portStr) {
				continue
			}
			slog.Warn("suspicious outbound port", "port", port, "category", portName)

			event := map[string]interface{}{
				"type":     "outbound_connection",
				"severity": "critical",
				"payload": map[string]interface{}{
					"connection_line": line,
					"port":            port,
					"port_name":       portName,
				},
				"occurred_at": time.Now(),
			}
			if err := m.client.ReportEvent(event); err != nil {
				slog.Warn("event report failed", "err", err)
			}
		}
	}
}
