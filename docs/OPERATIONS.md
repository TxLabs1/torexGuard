# Torex Guard — Operations Runbook

This document covers the day-to-day operational concerns of running Torex Guard
on a single VPS with the supplied `docker-compose.prod.yml` stack.

## Stack overview

```
+---------------------+      +---------------------------+
|   Caddy (TLS)       | ---> |   nginx + php-fpm (app)   |
|   automatic LE      |      +-------------+-------------+
+---------------------+                    |
        |                                  v
        v                          +---------------+
 +-------------+                    |    MySQL 8    |
 |   Reverb    |                    +---------------+
 +-------------+                    |    Redis 7    |
                                    +---------------+
                                    |   Horizon     |
                                    +---------------+
                                    |   Queue       |
                                    +---------------+
                                    |   Backup cron |
                                    +---------------+
```

## Backups

We use [`spatie/laravel-backup`](https://spatie.be/docs/laravel-backup) with an
off-site S3-compatible bucket (Backblaze B2, Hetzner Object Storage or Wasabi
all work). The schedule is registered in `routes/console.php`:

| Time  | Command                  | Purpose                                |
|-------|--------------------------|----------------------------------------|
| 01:00 | `backup:clean`           | Prune archives per retention policy    |
| 01:30 | `backup:run --only-db`   | Lightweight DB-only snapshot           |
| 02:00 | `backup:run`             | Full snapshot (DB + storage + geoip)   |
| 07:00 | `backup:monitor`         | Verify off-site bucket has a recent archive |

Configuration lives in `config/backup.php`; the off-site bucket is configured as
the `backups` disk in `config/filesystems.php` driven by `BACKUP_S3_*` env
variables.

### Restore runbook

> Goal: bring a clean MySQL database and storage tree back online from the
> latest off-site archive in **< 30 minutes**.

1. **Provision a clean host** (Docker + compose installed, no application data).
2. **Copy `.env`** from your secret store. At minimum it must contain valid
   `APP_KEY`, `DB_*`, `BACKUP_S3_*`, `REVERB_*`, and `MAIL_*` values.
3. **Pull the application image:**
   ```bash
   docker compose -f docker/docker-compose.prod.yml pull
   ```
4. **Start the database + cache only:**
   ```bash
   docker compose -f docker/docker-compose.prod.yml up -d mysql redis
   ```
5. **List off-site archives** to confirm what you are restoring:
   ```bash
   docker compose run --rm app php artisan backup:list
   ```
6. **Download the most recent archive** from S3 to `/tmp/restore.zip`:
   ```bash
   aws s3 cp s3://$BACKUP_S3_BUCKET/torex-<APP_NAME>/<archive>.zip /tmp/restore.zip \
     --endpoint-url $BACKUP_S3_ENDPOINT
   ```
   If `BACKUP_ARCHIVE_PASSWORD` is set, you'll need it to extract.
7. **Unzip** and locate the SQL dump:
   ```bash
   unzip -P "$BACKUP_ARCHIVE_PASSWORD" /tmp/restore.zip -d /tmp/restore
   ls /tmp/restore/db-dumps/
   ```
8. **Restore the database:**
   ```bash
   gunzip -c /tmp/restore/db-dumps/mysql-*.sql.gz | \
     docker compose exec -T mysql mysql -u root -p$DB_PASSWORD $DB_DATABASE
   ```
9. **Restore files** (storage/quarantine/geoip) by syncing back into the
   `storage/` volume:
   ```bash
   rsync -av /tmp/restore/storage/  ./storage/
   rsync -av /tmp/restore/quarantine/ ./storage/quarantine/
   rsync -av /tmp/restore/geoip/   ./database/geoip/
   ```
10. **Start the full stack and run migrations:**
    ```bash
    docker compose -f docker/docker-compose.prod.yml up -d
    docker compose exec app php artisan migrate --force
    docker compose exec app php artisan config:cache route:cache view:cache
    ```
11. **Smoke test:**
    - `curl -sS https://<host>/up` -> `200`
    - log in as a known user
    - confirm the agent dashboard shows the expected servers + threats
    - confirm Horizon reports its queues healthy at `/horizon`

### Disaster recovery RPO/RTO

- **RPO**: 24h (daily snapshot) -- DB-only snapshot at 01:30 keeps this closer
  to 22.5h. Lower it by scheduling more frequent `backup:run --only-db` runs.
- **RTO**: ~30 minutes once a new host is reachable and `.env` is in hand.

## Healthchecks

- App container: `/up` (Laravel health endpoint).
- MySQL: `mysqladmin ping`.
- Redis: `redis-cli ping`.
- Caddy: built-in.

## Deploy

`make deploy` (provided in the production compose) performs:

1. `docker compose pull`
2. `docker compose run --rm app php artisan migrate --force`
3. `docker compose run --rm app php artisan config:cache route:cache view:cache`
4. `docker compose up -d --no-deps app horizon queue reverb`
5. `docker compose exec app php artisan horizon:terminate` (graceful restart)

## On-call escalation

1. Sentry alert -> PagerDuty -> rotation -> Slack `#incidents`.
2. If Sentry surfaces a `MaximumAgeInDays` backup health-check failure, treat as
   `SEV-3`; investigate within one business day.
3. If MySQL fails a write, treat as `SEV-1`.
