package scanner

import (
	"os"
	"path/filepath"
	"testing"

	"github.com/torexlabs/guard-agent/internal/comms"
)

func writeTempFile(t *testing.T, name, content string) string {
	t.Helper()
	dir := t.TempDir()
	p := filepath.Join(dir, name)
	if err := os.WriteFile(p, []byte(content), 0o644); err != nil {
		t.Fatal(err)
	}
	return p
}

func TestScanFile_DetectsPHPWebShell(t *testing.T) {
	s := &Scanner{config: &comms.Config{}}

	path := writeTempFile(t, "evil.php", `<?php eval(base64_decode("..."));`)
	info, _ := os.Stat(path)

	r := s.scanFile(path, info)
	if r == nil {
		t.Fatal("expected detection, got nil")
	}
	if r.Severity != "critical" || r.Type != "web_shell" {
		t.Errorf("unexpected classification: type=%q severity=%q", r.Type, r.Severity)
	}
	if len(r.SignaturesMatched) == 0 {
		t.Error("expected at least one matched signature")
	}
}

func TestScanFile_IgnoresCleanPHP(t *testing.T) {
	s := &Scanner{config: &comms.Config{}}

	path := writeTempFile(t, "ok.php", `<?php echo "hello";`)
	info, _ := os.Stat(path)

	if r := s.scanFile(path, info); r != nil {
		t.Errorf("expected nil for clean file, got %+v", r)
	}
}

func TestScanFile_DetectsCryptoMiner(t *testing.T) {
	s := &Scanner{config: &comms.Config{}}

	path := writeTempFile(t, "miner.sh", `#!/bin/sh\nxmrig --url stratum+tcp://pool.example.com:3333`)
	info, _ := os.Stat(path)

	r := s.scanFile(path, info)
	if r == nil {
		t.Fatal("expected detection, got nil")
	}
	if r.Type != "crypto_miner" {
		t.Errorf("expected type=crypto_miner, got %q", r.Type)
	}
}

func TestScanFile_RespectsMaxSize(t *testing.T) {
	s := &Scanner{config: &comms.Config{}}

	// Build a file just over the 8MB threshold.
	big := make([]byte, maxScanSize+1)
	for i := range big {
		big[i] = 'a'
	}
	dir := t.TempDir()
	p := filepath.Join(dir, "huge.php")
	if err := os.WriteFile(p, big, 0o644); err != nil {
		t.Fatal(err)
	}
	info, _ := os.Stat(p)
	if r := s.scanFile(p, info); r != nil {
		t.Errorf("expected nil for oversize file, got %+v", r)
	}
}

func TestIsExcluded(t *testing.T) {
	s := &Scanner{config: &comms.Config{ExcludedPaths: []string{"/vendor/", "/node_modules/"}}}
	cases := map[string]bool{
		"/app/vendor/foo.php":       true,
		"/app/node_modules/bar.js":  true,
		"/app/src/Controller.php":   false,
	}
	for path, want := range cases {
		if got := s.isExcluded(path); got != want {
			t.Errorf("isExcluded(%q) = %v, want %v", path, got, want)
		}
	}
}
