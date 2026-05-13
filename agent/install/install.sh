#!/usr/bin/env bash
# Torex Guard agent installer (Linux + macOS).
#
# Usage:
#   curl -fsSL https://<dashboard-host>/install.sh | bash -s -- \
#        --api-key tg_xxxxxxxxxxxxxxxx \
#        --host    https://<dashboard-host>
#
# Required:
#   --api-key   Project API key (only on the first install per host)
#
# Optional:
#   --host      Control plane URL. Defaults to https://guard.torexlabs.cloud
#   --version   Pin a specific agent release (defaults to latest)
#   --prefix    Install prefix. Defaults to /usr/local

set -euo pipefail

GITHUB_REPO="${TOREX_RELEASE_REPO:-torexlabs/torex-guard}"
PREFIX="/usr/local"
VERSION=""
API_KEY=""
HOST="https://guard.torexlabs.cloud"

log() { printf '[torex-guard] %s\n' "$*"; }
die() { printf '[torex-guard] ERROR: %s\n' "$*" >&2; exit 1; }

while [[ $# -gt 0 ]]; do
  case "$1" in
    --api-key) API_KEY="$2"; shift 2 ;;
    --host)    HOST="$2"; shift 2 ;;
    --version) VERSION="$2"; shift 2 ;;
    --prefix)  PREFIX="$2"; shift 2 ;;
    *)         die "unknown flag: $1" ;;
  esac
done

[[ -z "$API_KEY" ]] && die "--api-key is required"

OS_RAW="$(uname -s)"
ARCH_RAW="$(uname -m)"

case "$OS_RAW" in
  Linux)  OS=linux ;;
  Darwin) OS=darwin ;;
  *) die "unsupported OS: $OS_RAW" ;;
esac

case "$ARCH_RAW" in
  x86_64|amd64)   ARCH=amd64 ;;
  aarch64|arm64)  ARCH=arm64 ;;
  *) die "unsupported arch: $ARCH_RAW" ;;
esac

if [[ -z "$VERSION" ]]; then
  log "looking up latest release"
  VERSION="$(curl -fsSL "https://api.github.com/repos/${GITHUB_REPO}/releases/latest" \
    | grep -o '"tag_name": *"[^"]*"' | head -1 | sed 's/.*"\([^"]*\)".*/\1/')"
  [[ -z "$VERSION" ]] && die "could not determine latest release"
fi
VERSION="${VERSION#v}"

TARBALL="torex-agent_${VERSION}_${OS}_${ARCH}.tar.gz"
URL="https://github.com/${GITHUB_REPO}/releases/download/v${VERSION}/${TARBALL}"
SUMS_URL="https://github.com/${GITHUB_REPO}/releases/download/v${VERSION}/SHA256SUMS"

log "downloading $URL"
TMPDIR="$(mktemp -d)"
trap 'rm -rf "$TMPDIR"' EXIT

curl -fsSL -o "$TMPDIR/$TARBALL" "$URL"
curl -fsSL -o "$TMPDIR/SHA256SUMS" "$SUMS_URL"

log "verifying SHA256"
( cd "$TMPDIR" && grep " $TARBALL$" SHA256SUMS | sha256sum -c - ) \
  || die "checksum verification failed"

log "extracting"
tar -xzf "$TMPDIR/$TARBALL" -C "$TMPDIR"

INSTALL_BIN="$PREFIX/bin/torex-agent"
sudo mkdir -p "$PREFIX/bin" /etc/torex-agent /var/torex-guard/quarantine
sudo install -m 0755 "$TMPDIR/torex-agent" "$INSTALL_BIN"

CFG=/etc/torex-agent/config.yaml
if [[ ! -f "$CFG" ]]; then
  log "writing $CFG"
  sudo tee "$CFG" >/dev/null <<EOF
api_key: ${API_KEY}
host: ${HOST}
scan_interval: 300
heartbeat_interval: 60
monitored_paths:
  - /var/www
  - /srv/www
excluded_paths:
  - /vendor
  - /node_modules
  - /.git
EOF
  sudo chmod 600 "$CFG"
fi

# systemd unit (Linux only)
if [[ "$OS" == "linux" ]] && command -v systemctl >/dev/null 2>&1; then
  UNIT=/etc/systemd/system/torex-agent.service
  if [[ ! -f "$UNIT" ]]; then
    log "writing $UNIT"
    sudo tee "$UNIT" >/dev/null <<EOF
[Unit]
Description=Torex Guard Agent
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
ExecStart=${INSTALL_BIN} --config /etc/torex-agent/config.yaml --log-format json
Restart=on-failure
RestartSec=5
NoNewPrivileges=true
ProtectSystem=strict
ReadWritePaths=/var/torex-guard /etc/torex-agent
ProtectHome=true
PrivateTmp=true

[Install]
WantedBy=multi-user.target
EOF
    sudo systemctl daemon-reload
  fi
  sudo systemctl enable --now torex-agent.service
  log "agent installed and started (systemctl status torex-agent)"
else
  log "agent installed at $INSTALL_BIN"
  log "start with: $INSTALL_BIN --config $CFG --daemon"
fi
