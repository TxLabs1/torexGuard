// Package executor handles commands sent by the Torex Guard control plane.
//
// The dashboard creates a row in the `actions` table when an operator clicks
// "Quarantine", "Kill process", etc. The agent long-polls /commands/pending,
// runs the requested action locally, and PATCHes the result back. The server
// is the source of truth for state machine transitions.
package executor

import (
	"context"
	"errors"
	"fmt"
	"io"
	"log/slog"
	"os"
	"path/filepath"
	"strconv"
	"strings"
	"time"

	"github.com/torexlabs/guard-agent/internal/comms"
)

type Executor struct {
	client         *comms.Client
	quarantinePath string
}

func New(client *comms.Client, quarantinePath string) *Executor {
	if quarantinePath == "" {
		quarantinePath = "/var/torex-guard/quarantine"
	}
	return &Executor{client: client, quarantinePath: quarantinePath}
}

// Run polls for pending commands on `interval` and dispatches them. Returns
// only when `stop` is closed or the context is cancelled.
func (e *Executor) Run(ctx context.Context, stop chan struct{}, interval time.Duration) {
	defer func() {
		if r := recover(); r != nil {
			slog.Error("executor panic", "recover", r)
		}
	}()

	if interval == 0 {
		interval = 10 * time.Second
	}
	ticker := time.NewTicker(interval)
	defer ticker.Stop()

	for {
		select {
		case <-ticker.C:
			e.tick(ctx)
		case <-stop:
			return
		case <-ctx.Done():
			return
		}
	}
}

func (e *Executor) tick(ctx context.Context) {
	cmds, err := e.client.FetchCommands()
	if err != nil {
		slog.Warn("fetch commands failed", "err", err)
		return
	}
	for _, c := range cmds {
		select {
		case <-ctx.Done():
			return
		default:
		}
		result, status, errStr := e.dispatch(c)
		if err := e.client.AckCommand(c.ID, status, result, errStr); err != nil {
			slog.Warn("ack command failed", "id", c.ID, "err", err)
		}
	}
}

func (e *Executor) dispatch(cmd comms.PendingCommand) (result, status, errStr string) {
	defer func() {
		if r := recover(); r != nil {
			status = "failed"
			errStr = fmt.Sprintf("panic: %v", r)
		}
	}()

	slog.Info("executing command", "id", cmd.ID, "type", cmd.Type, "target", cmd.Target)

	switch strings.ToLower(cmd.Type) {
	case "quarantine_file":
		return e.quarantine(cmd.Target)
	case "delete_file":
		return e.delete(cmd.Target)
	case "chmod_lock":
		return e.chmodLock(cmd.Target)
	case "kill_process":
		return e.killProcess(cmd.Target)
	case "send_alert":
		// "send_alert" with target=="force_scan" is the dashboard's way of
		// signalling a one-off scan request via the existing enum.
		if cmd.Target == "force_scan" {
			return "scan requested", "success", ""
		}
		return "ack", "success", ""
	default:
		return "", "failed", fmt.Sprintf("unsupported command type: %s", cmd.Type)
	}
}

func (e *Executor) quarantine(path string) (string, string, string) {
	if path == "" {
		return "", "failed", "missing target"
	}
	if _, err := os.Stat(path); err != nil {
		return "", "failed", fmt.Sprintf("stat: %v", err)
	}

	if err := os.MkdirAll(e.quarantinePath, 0o700); err != nil {
		return "", "failed", fmt.Sprintf("mkdir quarantine: %v", err)
	}

	dest := filepath.Join(e.quarantinePath,
		fmt.Sprintf("%d-%s.bin", time.Now().UnixNano(), filepath.Base(path)))

	if err := safeRename(path, dest); err != nil {
		return "", "failed", fmt.Sprintf("rename: %v", err)
	}

	// Strip permissions so even root casually browsing can't trip on it.
	_ = os.Chmod(dest, 0o000)

	return fmt.Sprintf("moved %s to %s", path, dest), "success", ""
}

func (e *Executor) delete(path string) (string, string, string) {
	if path == "" {
		return "", "failed", "missing target"
	}
	if err := os.Remove(path); err != nil {
		return "", "failed", err.Error()
	}
	return "deleted " + path, "success", ""
}

func (e *Executor) chmodLock(path string) (string, string, string) {
	if path == "" {
		return "", "failed", "missing target"
	}
	if err := os.Chmod(path, 0o000); err != nil {
		return "", "failed", err.Error()
	}
	return "chmod 000 " + path, "success", ""
}

func (e *Executor) killProcess(target string) (string, string, string) {
	pid, err := strconv.Atoi(strings.TrimSpace(target))
	if err != nil {
		return "", "failed", "invalid pid: " + target
	}
	proc, err := os.FindProcess(pid)
	if err != nil {
		return "", "failed", err.Error()
	}
	if err := proc.Kill(); err != nil {
		return "", "failed", err.Error()
	}
	return fmt.Sprintf("killed pid %d", pid), "success", ""
}

// safeRename falls back to copy+remove when src and dst are on different
// filesystems (EXDEV is common when quarantining onto a separate mount).
func safeRename(src, dst string) error {
	if err := os.Rename(src, dst); err == nil {
		return nil
	} else if !isCrossDevice(err) {
		return err
	}

	in, err := os.Open(src)
	if err != nil {
		return err
	}
	defer in.Close()

	out, err := os.OpenFile(dst, os.O_WRONLY|os.O_CREATE|os.O_TRUNC, 0o600)
	if err != nil {
		return err
	}
	defer out.Close()

	if _, err := io.Copy(out, in); err != nil {
		return err
	}
	return os.Remove(src)
}

func isCrossDevice(err error) bool {
	if err == nil {
		return false
	}
	var le *os.LinkError
	if errors.As(err, &le) {
		return strings.Contains(le.Err.Error(), "cross-device")
	}
	return false
}
