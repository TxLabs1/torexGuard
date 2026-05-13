# Architecture

## Components

```mermaid
flowchart LR
    Browser((Operator browser)) -- HTTPS 443 --> Caddy[Caddy reverse proxy + TLS]
    Caddy --> App[php-fpm + nginx]
    Caddy --> Reverb[(Laravel Reverb WS)]
    Agent((Go agent on customer host)) -- HTTPS + Bearer token --> Caddy
    App --> MySQL[(MySQL 8)]
    App --> Redis[(Redis 7)]
    App --> Horizon[Horizon dashboard]
    App --> Worker[Queue worker]
    Worker --> Redis
    Horizon --> Redis
    Reverb --> Redis
    App -. errors .-> Sentry[(Sentry)]
    Backup[Spatie Backup] -. daily .-> S3[(Off-site S3 bucket)]
```

## Data model (excerpt)

```
organizations 1───* users
              1───* projects 1───* servers 1───* agents
                                  └───* threats 1───* threat_logs
                                                 └───* actions (agent commands)
                                                 └───* alerts
              1───* notification_settings
```

Every domain model that lives under an `organizations` row carries a global
[`OrganizationScope`](../app/Models/Scopes/OrganizationScope.php). Queries made
by an authenticated dashboard user are silently filtered to their organization.
Agent and queue contexts opt out by calling
`App\Support\OrganizationContext::asSystem()`.

## Request flow

### Dashboard read

```mermaid
sequenceDiagram
    autonumber
    participant U as User browser
    participant C as Caddy
    participant A as nginx + php-fpm
    participant D as MySQL
    U->>C: GET /threats (cookie + CSRF)
    C->>A: Reverse proxy
    A->>A: Auth + Sanctum stateful + OrganizationContext::setCurrent()
    A->>D: SELECT * FROM threats WHERE organization_id = ?
    D-->>A: rows
    A-->>C: 200 HTML
    C-->>U: 200 HTML
```

### Agent threat ingestion

```mermaid
sequenceDiagram
    autonumber
    participant Agent
    participant Caddy
    participant App as Laravel
    participant Q as Redis (events)
    participant Job as ProcessThreatEvent
    participant DB as MySQL
    participant WS as Reverb

    Agent->>Caddy: POST /api/v1/agent/threats (Bearer token)
    Caddy->>App: Reverse proxy
    App->>App: AgentAuthenticate middleware (sha256 hash lookup)
    App->>DB: INSERT INTO threats, alerts
    App->>WS: broadcast ThreatDetected, AlertCreated
    App->>Q: dispatch ProcessThreatEvent (notifications + auto-quarantine)
    App-->>Agent: 201 created
    Job->>DB: enrich, mark processed
    Job->>WS: notify users via database + mail
```

### Command channel (operator → agent)

```mermaid
sequenceDiagram
    Operator->>App: POST /threats/{id}/quarantine
    App->>DB: INSERT INTO actions (status=pending)
    App-->>Operator: 200
    Agent->>App: GET /api/v1/agent/commands/pending
    App->>DB: SELECT pending actions for server
    DB-->>App: rows -> transition to executing
    App-->>Agent: 200 [{id, type, target}]
    Agent->>Agent: dispatch -> safeRename / kill / chmod
    Agent->>App: PATCH /api/v1/agent/commands/{id} {status=success}
    App->>DB: UPDATE actions SET status='success'
```

## Background pipeline

| Queue          | Purpose                                                       |
|----------------|---------------------------------------------------------------|
| `events`       | `ProcessThreatEvent`, `ScanCompletedJob`                      |
| `notifications`| `ThreatDetectedNotification`, `ServerOfflineNotification`, `WeeklyDigestNotification` |
| `default`      | Sanctum prune, schedule fan-out                               |

Scheduled commands (registered in `routes/console.php`):

- `torex:heartbeat-sweep` -- every minute, flips stale servers to offline
- `torex:weekly-digest`   -- Monday 08:00, sends weekly digest
- `backup:run` / `backup:run --only-db` / `backup:clean` / `backup:monitor`
- `activitylog:clean` -- prune Spatie activity log
