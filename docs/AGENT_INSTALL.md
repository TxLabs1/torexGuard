# Installing the Torex Guard agent

The agent is a single statically-linked Go binary. Cross-platform releases are
built by GitHub Actions on every `v*` tag using
[`agent/.goreleaser.yaml`](../agent/.goreleaser.yaml) and published to the
GitHub Releases page along with a `checksums.txt`.

## Linux / macOS

```bash
curl -fsSL https://<your-domain>/install.sh | sudo \
    TOREX_API_KEY=tk_xxxxxxxxxxxxxxxx \
    TOREX_HOST=https://<your-domain> \
    bash
```

What it does:

1. Reads `latest` release from `github.com/torexlabs/torex-guard` (override
   via `TOREX_VERSION=v1.0.0`).
2. Downloads `torex-agent_<os>_<arch>.tar.gz` plus `checksums.txt`.
3. Verifies the SHA256 of the archive against `checksums.txt`.
4. Extracts to `/usr/local/bin/torex-agent` (`chmod 0755`).
5. Writes `/etc/torex-agent/config.yaml` with sensible defaults and the API
   key you provided.
6. Registers a `systemd` unit (`/etc/systemd/system/torex-agent.service`) and
   starts it.

Verify:

```bash
systemctl status torex-agent
journalctl -u torex-agent -f
```

## Windows

Run PowerShell as administrator:

```powershell
$env:TOREX_API_KEY = "tk_xxxxxxxxxxxxxxxx"
$env:TOREX_HOST    = "https://<your-domain>"
iwr -useb https://<your-domain>/install.ps1 | iex
```

The installer writes the binary to `C:\Program Files\Torex Guard\torex-agent.exe`,
config to `C:\ProgramData\Torex Guard\config.yaml`, and registers a Windows
service named `TorexGuardAgent`.

## Config reference

`config.yaml`:

```yaml
host: https://<your-domain>
api_key: tk_xxxxxxxxxxxxxxxx
hostname: ""              # auto-detected when blank
scan_interval: 300         # seconds
heartbeat_interval: 60
monitored_paths:
  - /var/www
  - /home/*/public_html
excluded_paths:
  - /vendor
  - /node_modules
  - /.git
auto_quarantine: false
realtime_monitoring: true
log_level: info            # debug | info | warn | error
```

Values can be overridden via command-line flags:

```
torex-agent --config /etc/torex-agent/config.yaml \
            --paths /var/www,/srv/app \
            --scan-interval 600
```

## Uninstalling

```bash
sudo systemctl disable --now torex-agent
sudo rm /etc/systemd/system/torex-agent.service \
        /etc/torex-agent/config.yaml \
        /usr/local/bin/torex-agent
sudo systemctl daemon-reload
```

On Windows:

```powershell
sc.exe delete TorexGuardAgent
Remove-Item -Recurse "$Env:ProgramFiles\Torex Guard"
Remove-Item -Recurse "$Env:ProgramData\Torex Guard"
```

## Troubleshooting

- **Cannot register: 401**: the API key has been rotated. Re-issue from the
  dashboard (`Settings > Project > Rotate API key`) and replace `api_key` in
  `/etc/torex-agent/config.yaml`.
- **`x509: certificate signed by unknown authority`**: the host is using a
  self-signed cert. Trust it system-wide (`update-ca-certificates` on Debian)
  or, for testing only, set `insecure_tls: true` in `config.yaml`.
- **Cross-device quarantine errors**: the agent falls back to copy+remove when
  the quarantine path is on a different mount. Make sure
  `/var/torex-guard/quarantine` is writable.
