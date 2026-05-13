package executor

import (
	"os"
	"path/filepath"
	"strings"
	"testing"

	"github.com/torexlabs/guard-agent/internal/comms"
)

func newTestExecutor(t *testing.T) (*Executor, string) {
	t.Helper()
	quarantine := filepath.Join(t.TempDir(), "quarantine")
	return New(nil, quarantine), quarantine
}

func TestDispatch_QuarantineFile(t *testing.T) {
	e, qdir := newTestExecutor(t)

	target := filepath.Join(t.TempDir(), "bad.php")
	if err := os.WriteFile(target, []byte("<?php evil"), 0o644); err != nil {
		t.Fatal(err)
	}

	result, status, errStr := e.dispatch(comms.PendingCommand{
		ID: 1, Type: "quarantine_file", Target: target,
	})

	if status != "success" {
		t.Fatalf("expected success, got status=%q err=%q", status, errStr)
	}
	if !strings.Contains(result, qdir) {
		t.Errorf("result should mention quarantine dir, got %q", result)
	}
	if _, err := os.Stat(target); !os.IsNotExist(err) {
		t.Error("source file should have been moved out of the path")
	}
}

func TestDispatch_QuarantineMissingTarget(t *testing.T) {
	e, _ := newTestExecutor(t)
	_, status, errStr := e.dispatch(comms.PendingCommand{
		ID: 2, Type: "quarantine_file", Target: "",
	})
	if status != "failed" || errStr == "" {
		t.Errorf("expected failed status with error, got %q / %q", status, errStr)
	}
}

func TestDispatch_DeleteFile(t *testing.T) {
	e, _ := newTestExecutor(t)
	target := filepath.Join(t.TempDir(), "doomed.txt")
	if err := os.WriteFile(target, []byte("x"), 0o644); err != nil {
		t.Fatal(err)
	}

	_, status, errStr := e.dispatch(comms.PendingCommand{
		ID: 3, Type: "delete_file", Target: target,
	})
	if status != "success" {
		t.Fatalf("expected success, got %q / %q", status, errStr)
	}
	if _, err := os.Stat(target); !os.IsNotExist(err) {
		t.Error("file should be gone")
	}
}

func TestDispatch_UnknownType(t *testing.T) {
	e, _ := newTestExecutor(t)
	_, status, errStr := e.dispatch(comms.PendingCommand{
		ID: 4, Type: "kabloom", Target: "/etc/hosts",
	})
	if status != "failed" || !strings.Contains(errStr, "unsupported") {
		t.Errorf("expected unsupported error, got status=%q err=%q", status, errStr)
	}
}

func TestDispatch_KillProcessInvalidPid(t *testing.T) {
	e, _ := newTestExecutor(t)
	_, status, errStr := e.dispatch(comms.PendingCommand{
		ID: 5, Type: "kill_process", Target: "not-a-number",
	})
	if status != "failed" || !strings.Contains(errStr, "invalid pid") {
		t.Errorf("expected invalid pid error, got %q / %q", status, errStr)
	}
}
