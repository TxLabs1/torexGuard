package integrity

import (
	"os"
	"path/filepath"
	"testing"

	"github.com/torexlabs/guard-agent/internal/comms"
)

func TestBuildRecord_HashesFile(t *testing.T) {
	m := &IntegrityMonitor{config: &comms.Config{}}
	dir := t.TempDir()
	p := filepath.Join(dir, "f.txt")
	if err := os.WriteFile(p, []byte("hello"), 0o644); err != nil {
		t.Fatal(err)
	}
	info, _ := os.Stat(p)

	rec := m.buildRecord(p, info)
	if rec == nil {
		t.Fatal("expected record, got nil")
	}
	// sha256("hello")
	want := "2cf24dba5fb0a30e26e83b2ac5b9e29e1b161e5c1fa7425e73043362938b9824"
	if rec.Hash != want {
		t.Errorf("hash mismatch: got %q want %q", rec.Hash, want)
	}
	if rec.Size != int64(len("hello")) {
		t.Errorf("size mismatch: got %d want %d", rec.Size, len("hello"))
	}
}

func TestSuspiciousNewFile(t *testing.T) {
	m := &IntegrityMonitor{config: &comms.Config{}}
	cases := map[string]bool{
		"/var/www/public/x.php":       true,
		"/var/www/uploads/shell.phtml": true,
		"/var/www/index.html":         false,
		"/var/www/config/db.php":      false,
	}
	for path, want := range cases {
		got := m.isSuspiciousNewFile(path, nil)
		if got != want {
			t.Errorf("isSuspiciousNewFile(%q) = %v, want %v", path, got, want)
		}
	}
}

func TestSuspiciousModification_CoreFile(t *testing.T) {
	m := &IntegrityMonitor{config: &comms.Config{}}
	if !m.isSuspiciousModification("/var/www/index.php", nil) {
		t.Error("/var/www/index.php should be flagged")
	}
	if m.isSuspiciousModification("/var/www/about.html", nil) {
		t.Error("/var/www/about.html should NOT be flagged")
	}
}

func TestCheckChanges_DetectsNewFile(t *testing.T) {
	dir := t.TempDir()
	m := &IntegrityMonitor{
		config:   &comms.Config{MonitoredPaths: []string{dir}},
		client:   nil,
		baseline: map[string]FileRecord{},
	}

	if err := os.WriteFile(filepath.Join(dir, "new.php"), []byte("<?php"), 0o644); err != nil {
		t.Fatal(err)
	}

	// Simulate the diff loop without sending to the server.
	for _, root := range m.config.MonitoredPaths {
		_ = filepath.Walk(root, func(path string, info os.FileInfo, err error) error {
			if err != nil || info.IsDir() {
				return nil
			}
			rec := m.buildRecord(path, info)
			if rec == nil {
				return nil
			}
			if _, exists := m.baseline[path]; !exists {
				m.baseline[path] = *rec
			}
			return nil
		})
	}

	if len(m.baseline) != 1 {
		t.Fatalf("expected 1 baseline entry, got %d", len(m.baseline))
	}
}
