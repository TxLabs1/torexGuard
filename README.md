# Torex Guard

**Runtime application security and malware detection for Laravel + Python infrastructures.**

Torex Guard is a self-hostable control plane plus a Go agent that runs on each
server you operate. The agent continuously scans files, monitors integrity,
watches processes/network, and reports findings to the control plane. Operators
triage threats from a Laravel-powered dashboard with real-time updates, 2FA,
GeoIP enrichment, RBAC, audit log, and broadcast notifications.

## Stack

| Layer            | Technology                                  |
|------------------|---------------------------------------------|
| Backend API      | Laravel 12, MySQL 8, Redis 7                |
| Real-time        | Laravel Reverb (WebSockets)                 |
| Queue            | Laravel Horizon                             |
| Auth             | Sanctum SPA + 2FA (TOTP + recovery codes)   |
| Frontend         | Blade + Bootstrap 5 + Echo                  |
| Agent            | Go 1.22, gopsutil, YAML config              |
| Infrastructure   | Docker, Nginx, php-fpm, Caddy (TLS)         |
| Observability    | Sentry, structured slog, Spatie Activitylog |

## Quick start (single VPS, production)

1. Pre-requisites on the host:
   - Docker 24+ with the compose plugin
   - DNS A/AAAA record pointing `APP_DOMAIN` at the VPS
2. Clone and configure:

   ```bash
   git clone https://github.com/torexlabs/torex-guard.git
   cd torex-guard
   cp .env.example .env
   ```

   Fill in `APP_KEY` (`docker compose run --rm app php artisan key:generate`),
   `APP_DOMAIN`, `DB_*`, `REDIS_*`, `REVERB_*`, `MAIL_*`, optional `SENTRY_*`,
   `GEOIP_LICENSE_KEY`, and `BACKUP_S3_*`.
3. Bring the stack up:

   ```bash
   make deploy
   ```

4. Create your first super-admin and seed roles:

   ```bash
   docker compose -f docker/docker-compose.prod.yml exec app \
       php artisan db:seed --class=RolesAndPermissionsSeeder --force
   ```

## Local development

```bash
docker compose -f docker/docker-compose.yml up -d
docker compose -f docker/docker-compose.yml exec app php artisan migrate --seed
```

The dev compose ships MySQL, Redis, Reverb, Horizon and `php artisan serve`
with a bind-mounted source tree.

## Documentation

| Document                                | What you'll find                                              |
|-----------------------------------------|---------------------------------------------------------------|
| [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)   | Components, request flow, sequence diagrams           |
| [docs/AGENT_INSTALL.md](docs/AGENT_INSTALL.md) | Installing the Go agent on Linux / macOS / Windows    |
| [docs/API_REFERENCE.md](docs/API_REFERENCE.md) | Agent and dashboard API surface                       |
| [docs/SECURITY.md](docs/SECURITY.md)           | Threat model, disclosure policy, defensive controls   |
| [docs/OPERATIONS.md](docs/OPERATIONS.md)       | Backups, restore runbook, deploy, monitoring          |

## Agent install (TL;DR)

After creating a project in the dashboard you'll be shown a command like:

```bash
curl -fsSL https://<your-domain>/install.sh | sudo \
    TOREX_API_KEY=tk_xxxxxx \
    TOREX_HOST=https://<your-domain> \
    bash
```

The script downloads the latest signed release from GitHub, verifies the
SHA256SUMS, drops a config at `/etc/torex-agent/config.yaml`, and starts a
`systemd` unit. Windows uses [`install.ps1`](agent/install/install.ps1).

## License

Proprietary. See [LICENSE](LICENSE).
