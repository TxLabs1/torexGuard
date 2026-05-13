// Package scanner provides regex-based malware signature scanning over
// the configured filesystem paths.
package scanner

import (
	"crypto/sha256"
	"encoding/hex"
	"fmt"
	"log/slog"
	"os"
	"path/filepath"
	"regexp"
	"strings"
	"sync"
	"time"

	"github.com/torexlabs/guard-agent/internal/comms"
)

var phpSignatures = []*regexp.Regexp{
	regexp.MustCompile(`(?i)eval\s*\(\s*base64_decode`),
	regexp.MustCompile(`(?i)eval\s*\(\s*gzinflate`),
	regexp.MustCompile(`(?i)eval\s*\(\s*str_rot13`),
	regexp.MustCompile(`(?i)shell_exec\s*\(`),
	regexp.MustCompile(`(?i)passthru\s*\(`),
	regexp.MustCompile(`(?i)system\s*\(`),
	regexp.MustCompile(`(?i)preg_replace\s*\(.*\/e`),
	regexp.MustCompile(`(?i)FilesMan|c99shell|r57shell|WSO\s+Shell`),
	regexp.MustCompile(`(?i)base64_decode.*gzinflate|gzuncompress.*base64`),
	regexp.MustCompile(`(?i)chr\(\d+\)\.chr\(\d+\)\.chr\(\d+\)`),
	regexp.MustCompile(`(?i)\$_POST\[.*\].*eval`),
	regexp.MustCompile(`(?i)assert\s*\(\s*\$_(POST|GET|REQUEST)`),
}

var pythonSignatures = []*regexp.Regexp{
	regexp.MustCompile(`(?i)subprocess\.Popen`),
	regexp.MustCompile(`(?i)os\.system\s*\(`),
	regexp.MustCompile(`(?i)pickle\.loads\s*\(`),
	regexp.MustCompile(`(?i)marshal\.loads\s*\(`),
	regexp.MustCompile(`(?i)exec\s*\(\s*compile`),
	regexp.MustCompile(`(?i)__import__\s*\(\s*['"]os`),
}

var cryptoMinerPatterns = []*regexp.Regexp{
	regexp.MustCompile(`(?i)xmrig|monero|cryptonight|coinhive`),
	regexp.MustCompile(`(?i)minerd|cpuminer`),
	regexp.MustCompile(`stratum\+tcp://`),
}

type ScanResult struct {
	FilesScanned int
	ThreatsFound int
	StartedAt    time.Time
	CompletedAt  time.Time
	Threats      []ThreatReport
}

type ThreatReport struct {
	Type              string    `json:"type"`
	Severity          string    `json:"severity"`
	Title             string    `json:"title"`
	FilePath          string    `json:"file_path"`
	FileHash          string    `json:"file_hash"`
	FileSize          int64     `json:"file_size"`
	ConfidenceScore   int       `json:"confidence_score"`
	SignaturesMatched []string  `json:"signatures_matched"`
	DetectedAt        time.Time `json:"detected_at"`
}

type Scanner struct {
	config *comms.Config
	client *comms.Client
}

func NewScanner(cfg *comms.Config, client *comms.Client) *Scanner {
	return &Scanner{config: cfg, client: client}
}

func (s *Scanner) RunFullScan() *ScanResult {
	defer func() {
		if r := recover(); r != nil {
			slog.Error("scanner panic", "recover", r)
		}
	}()

	result := &ScanResult{StartedAt: time.Now()}
	var mu sync.Mutex
	var wg sync.WaitGroup
	semaphore := make(chan struct{}, 5)

	paths := s.config.MonitoredPaths
	if len(paths) == 0 {
		paths = []string{"/var/www"}
	}

	for _, rootPath := range paths {
		_ = filepath.Walk(rootPath, func(path string, info os.FileInfo, err error) error {
			if err != nil || info.IsDir() || s.isExcluded(path) {
				return nil
			}
			wg.Add(1)
			semaphore <- struct{}{}
			go func(p string, fi os.FileInfo) {
				defer wg.Done()
				defer func() { <-semaphore }()

				mu.Lock()
				result.FilesScanned++
				mu.Unlock()

				if threat := s.scanFile(p, fi); threat != nil {
					mu.Lock()
					result.Threats = append(result.Threats, *threat)
					result.ThreatsFound++
					mu.Unlock()
					if err := s.client.ReportThreat(*threat); err != nil {
						slog.Warn("report threat failed", "err", err)
					}
				}
			}(path, info)
			return nil
		})
	}

	wg.Wait()
	result.CompletedAt = time.Now()
	return result
}

const maxScanSize = 8 * 1024 * 1024 // skip files >8MB to keep scan latency bounded

func (s *Scanner) scanFile(path string, info os.FileInfo) *ThreatReport {
	if info.Size() > maxScanSize {
		return nil
	}
	ext := strings.ToLower(filepath.Ext(path))
	content, err := os.ReadFile(path)
	if err != nil {
		return nil
	}

	var matched []string
	var threatType, severity string

	if ext == ".php" || ext == ".phtml" || ext == ".php5" {
		for _, sig := range phpSignatures {
			if sig.Match(content) {
				matched = append(matched, sig.String())
				threatType = "web_shell"
				severity = "critical"
			}
		}
	}

	if ext == ".py" {
		for _, sig := range pythonSignatures {
			if sig.Match(content) {
				matched = append(matched, sig.String())
				threatType = "malware"
				severity = "high"
			}
		}
	}

	for _, sig := range cryptoMinerPatterns {
		if sig.Match(content) {
			matched = append(matched, sig.String())
			threatType = "crypto_miner"
			severity = "high"
		}
	}

	if len(matched) == 0 {
		return nil
	}

	hash := sha256.Sum256(content)
	confidence := s.calculateConfidence(matched, len(content))

	slog.Warn("threat detected",
		"severity", severity,
		"type", threatType,
		"path", path,
		"matches", len(matched),
	)

	title := fmt.Sprintf("Malicious file detected: %s", filepath.Base(path))
	if ext != "" {
		title = fmt.Sprintf("Malicious %s detected: %s", strings.ToUpper(ext[1:]), filepath.Base(path))
	}

	return &ThreatReport{
		Type:              threatType,
		Severity:          severity,
		Title:             title,
		FilePath:          path,
		FileHash:          hex.EncodeToString(hash[:]),
		FileSize:          info.Size(),
		ConfidenceScore:   confidence,
		SignaturesMatched: matched,
		DetectedAt:        time.Now(),
	}
}

func (s *Scanner) calculateConfidence(matched []string, _ int) int {
	base := len(matched) * 20
	if base > 100 {
		base = 100
	}
	return base
}

func (s *Scanner) isExcluded(path string) bool {
	excluded := s.config.ExcludedPaths
	if len(excluded) == 0 {
		excluded = []string{"/vendor/", "/node_modules/", "/.git/", "/storage/framework/"}
	}
	for _, ex := range excluded {
		if strings.Contains(path, ex) {
			return true
		}
	}
	return false
}

func (s *Scanner) StartScheduled(stop chan struct{}) {
	defer func() {
		if r := recover(); r != nil {
			slog.Error("scheduled scan panic", "recover", r)
		}
	}()

	interval := s.config.ScanInterval
	if interval <= 0 {
		interval = 300
	}
	ticker := time.NewTicker(time.Duration(interval) * time.Second)
	defer ticker.Stop()

	for {
		select {
		case <-ticker.C:
			slog.Info("scheduled scan starting")
			result := s.RunFullScan()
			slog.Info("scheduled scan done",
				"files", result.FilesScanned,
				"threats", result.ThreatsFound,
				"duration", result.CompletedAt.Sub(result.StartedAt).String(),
			)
		case <-stop:
			return
		}
	}
}
