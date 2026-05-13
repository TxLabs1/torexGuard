# API Reference

All endpoints live under `https://<your-domain>`.

## Authentication

| Surface         | Mechanism                                          |
|-----------------|----------------------------------------------------|
| Dashboard UI    | Session cookie + CSRF token + Sanctum stateful API |
| Personal access | `Authorization: Bearer <user-token>`               |
| Agent           | `Authorization: Bearer <agent-token>`              |

Agent tokens are SHA-256 hashed at rest; the plaintext value is shown to the
agent exactly once at `/api/v1/agent/register` and never again.

## Rate limiting

| Limiter            | Limit                  | Scope                       |
|--------------------|------------------------|-----------------------------|
| `api`              | 60 req/min             | per IP                      |
| `agent-register`   | 5 req/min              | per IP                      |
| `agent`            | 1000 req/min           | per agent token             |
| `login`            | 10 req/min  + 5/email  | per IP / per email          |

Hitting a limiter returns `429 Too Many Requests` with `Retry-After`.

## Agent API

### `POST /api/v1/agent/register`

Onboards a host. Unauthenticated; rate-limited under `agent-register`.

Request body:

```json
{
  "project_api_key": "tk_xxxxxx",
  "hostname": "web-01",
  "os": "linux",
  "os_version": "Ubuntu 24.04",
  "arch": "amd64",
  "cpu_count": 4,
  "memory_total": 8589934592,
  "disk_total": 107374182400,
  "version": "1.0.0",
  "capabilities": ["scan", "integrity", "quarantine"]
}
```

Response:

```json
{
  "agent_id": "agt_3JxR....",
  "agent_token": "ag_q9X...",   // SHOWN ONCE
  "server_id": 42,
  "config": { "scan_interval": 300, "monitored_paths": ["/var/www"], ... }
}
```

### `POST /api/v1/agent/heartbeat`

Authenticated. Body accepts `cpu_usage`, `memory_usage`, `disk_usage`,
`ip_address` (all optional). Flips the server to `online` and bumps
`last_heartbeat_at`.

### `POST /api/v1/agent/threats`

Authenticated. Reports a single threat. Validated fields:

```
type, severity (low|medium|high|critical), title, description,
file_path, file_hash, file_size, source_ip, confidence_score,
detection_details, signatures_matched[], detected_at
```

### `POST /api/v1/agent/threats/batch`

Same shape under `threats[]` (max 100).

### `POST /api/v1/agent/events` / `POST /api/v1/agent/events/batch`

Generic security events. They land in `threat_events` and are processed
asynchronously by `ProcessThreatEvent` which may auto-escalate them to
threats.

### `GET /api/v1/agent/commands/pending`

Long-poll for actions queued by an operator. Returns:

```json
{
  "commands": [
    { "id": 7, "type": "quarantine_file", "target": "/var/www/x.php", "threat_id": 12, "automated": false }
  ]
}
```

The server transitions returned rows to `executing` to prevent redelivery.

### `PATCH /api/v1/agent/commands/{id}`

Acknowledge an action:

```json
{ "status": "success" | "failed" | "rolled_back", "result": "...", "error": "..." }
```

### `POST /api/v1/agent/scans` / `PATCH /scans/{id}` / `POST /scans/{id}/complete`

Lifecycle endpoints for ad-hoc and scheduled scans.

### `GET /api/v1/agent/signatures`

Returns the latest regex signature set the dashboard expects the agent to use.

## Dashboard API

The dashboard primarily uses server-rendered Blade. A small JSON API powers
the live tiles and event stream:

- `GET /api/v1/dashboard/stats`
- `GET /api/v1/dashboard/recent-threats`
- `GET /api/v1/dashboard/alerts/unread`
- `GET /api/v1/dashboard/event-stream` -- SSE fallback when WS is unavailable

Personal access tokens (issued under `Settings > API tokens`) authenticate via
`Authorization: Bearer <token>` and have the same per-org scope as the user
who issued them.

## WebSockets (Reverb)

Subscribe to the private channel `org.{orgId}` from the dashboard. Events:

| Event             | Payload                                                |
|-------------------|--------------------------------------------------------|
| `threat.detected` | `{ id, threat_id, severity, title, project_id, ... }`  |
| `alert.created`   | `{ id, severity, title, message, threat_id }`          |
| `server.status`   | `{ id, hostname, previous, current }`                  |
| `scan.completed`  | `{ id, scan_id, files_scanned, threats_found, ... }`   |
