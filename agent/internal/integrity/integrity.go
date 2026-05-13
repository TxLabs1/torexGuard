// Package integrity implements file integrity monitoring via SHA-256
// baselines + periodic change detection.
package integrity

import (
	"crypto/sha256"
	"encoding/hex"
	"fmt"
	"io"
	"log/slog"
	"os"
	"path/filepath"
	"strings"
	"sync"
	"time"

	"github.com/torexlabs/guard-agent/internal/comms"
)

type FileRecord struct {
	Path        string    `json:"path"`
	Hash        string    `json:"hash"`
	Size        int64     `json:"size"`
	Permissions string    `json:"permissions"`
	Owner       string    `json:"owner"`
	ModifiedAt  time.Time `json:"modified_at"`
}

type FileChange struct {
	Path        string `json:"path"`
	Hash        string `json:"hash"`
	Size        int64  `json:"size"`
	Permissions string `json:"permissions"`
	ChangeType  string `json:"type"`
	Suspicious  bool   `json:"suspicious"`
}

type IntegrityMonitor struct {
	config   *comms.Config
	client   *comms.Client
	baseline map[string]FileRecord
	mu       sync.RWMutex
}

func NewIntegrityMonitor(cfg *comms.Config, client *comms.Client) *IntegrityMonitor {
	return &IntegrityMonitor{
		config:   cfg,
		client:   client,
		baseline: make(map[string]FileRecord),
	}
}

func (m *IntegrityMonitor) BuildBaseline() {
	defer func() {
		if r := recover(); r != nil {
			slog.Error("integrity baseline panic", "recover", r)
		}
	}()

	slog.Info("integrity baseline starting")
	records := []FileRecord{}

	for _, root := range m.config.MonitoredPaths {
		if err := filepath.Walk(root, func(path string, info os.FileInfo, err error) error {
			if err != nil || info.IsDir() {
				return nil
			}
			record := m.buildRecord(path, info)
			if record == nil {
				return nil
			}

			m.mu.Lock()
			m.baseline[path] = *record
			m.mu.Unlock()

			records = append(records, *record)
			return nil
		}); err != nil {
			slog.Warn("integrity walk failed", "root", root, "err", err)
		}
	}

	if len(records) > 0 {
		if err := m.client.SubmitSnapshot(records); err != nil {
			slog.Error("integrity baseline submit failed", "err", err)
		} else {
			slog.Info("integrity baseline submitted", "files", len(records))
		}
	}
}

func (m *IntegrityMonitor) Watch(stop chan struct{}) {
	defer func() {
		if r := recover(); r != nil {
			slog.Error("integrity watch panic", "recover", r)
		}
	}()

	ticker := time.NewTicker(30 * time.Second)
	defer ticker.Stop()

	for {
		select {
		case <-ticker.C:
			m.checkChanges()
		case <-stop:
			return
		}
	}
}

func (m *IntegrityMonitor) checkChanges() {
	var changes []FileChange

	m.mu.RLock()
	baseline := make(map[string]FileRecord, len(m.baseline))
	for k, v := range m.baseline {
		baseline[k] = v
	}
	m.mu.RUnlock()

	for _, root := range m.config.MonitoredPaths {
		_ = filepath.Walk(root, func(path string, info os.FileInfo, err error) error {
			if err != nil || info.IsDir() {
				return nil
			}
			current := m.buildRecord(path, info)
			if current == nil {
				return nil
			}

			existing, exists := baseline[path]

			switch {
			case !exists:
				changes = append(changes, FileChange{
					Path: path, Hash: current.Hash, Size: current.Size,
					Permissions: current.Permissions, ChangeType: "created",
					Suspicious: m.isSuspiciousNewFile(path, info),
				})
			case existing.Hash != current.Hash:
				changes = append(changes, FileChange{
					Path: path, Hash: current.Hash, Size: current.Size,
					Permissions: current.Permissions, ChangeType: "modified",
					Suspicious: m.isSuspiciousModification(path, info),
				})
			case existing.Permissions != current.Permissions:
				changes = append(changes, FileChange{
					Path: path, Permissions: current.Permissions,
					ChangeType: "permission_changed",
					Suspicious: current.Permissions == "0777" || current.Permissions == "0755",
				})
			}

			m.mu.Lock()
			m.baseline[path] = *current
			m.mu.Unlock()
			return nil
		})
	}

	if len(changes) == 0 {
		return
	}
	if err := m.client.ReportFileChanges(changes); err != nil {
		slog.Warn("integrity report failed", "err", err)
		return
	}

	flagged := 0
	for _, c := range changes {
		if c.Suspicious {
			flagged++
		}
	}
	slog.Info("integrity changes reported", "total", len(changes), "suspicious", flagged)
}

func (m *IntegrityMonitor) buildRecord(path string, info os.FileInfo) *FileRecord {
	f, err := os.Open(path)
	if err != nil {
		return nil
	}
	defer f.Close()

	h := sha256.New()
	if _, err := io.Copy(h, f); err != nil {
		return nil
	}

	return &FileRecord{
		Path:        path,
		Hash:        hex.EncodeToString(h.Sum(nil)),
		Size:        info.Size(),
		Permissions: fmt.Sprintf("%04o", info.Mode().Perm()),
		ModifiedAt:  info.ModTime(),
	}
}

var suspiciousNewFileExts = map[string]bool{
	".php": true, ".phtml": true, ".py": true, ".sh": true, ".bash": true,
}

var suspiciousNewFileDirs = []string{"/uploads/", "/public/", "/tmp/", "/storage/"}

func (m *IntegrityMonitor) isSuspiciousNewFile(path string, _ os.FileInfo) bool {
	if !suspiciousNewFileExts[strings.ToLower(filepath.Ext(path))] {
		return false
	}
	for _, dir := range suspiciousNewFileDirs {
		if strings.Contains(path, dir) {
			return true
		}
	}
	return false
}

var coreSentinelPaths = []string{"/index.php", "/wp-config.php", "/.env", "/artisan"}

func (m *IntegrityMonitor) isSuspiciousModification(path string, _ os.FileInfo) bool {
	for _, core := range coreSentinelPaths {
		if strings.Contains(path, core) {
			return true
		}
	}
	return false
}
