SHELL := /bin/bash

APP_IMAGE_TAG ?= latest
COMPOSE       ?= docker compose -f docker/docker-compose.prod.yml

.PHONY: help build pull deploy migrate cache horizon-restart logs ps stop

help:
	@echo "Available targets:"
	@echo "  build            Build the production app image"
	@echo "  pull             Pull the production app image"
	@echo "  deploy           Zero-downtime production deploy (migrate + restart)"
	@echo "  migrate          Run pending migrations against production DB"
	@echo "  cache            Rebuild config/route/view caches"
	@echo "  horizon-restart  Gracefully terminate Horizon so it restarts"
	@echo "  logs             Tail all production container logs"
	@echo "  ps               List running production containers"
	@echo "  stop             Stop the production stack"

build:
	$(COMPOSE) build app

pull:
	$(COMPOSE) pull

migrate:
	$(COMPOSE) run --rm app php artisan migrate --force

cache:
	$(COMPOSE) run --rm app php artisan config:cache
	$(COMPOSE) run --rm app php artisan route:cache
	$(COMPOSE) run --rm app php artisan view:cache

deploy: pull migrate cache
	$(COMPOSE) up -d --no-deps app horizon queue reverb scheduler
	$(MAKE) horizon-restart

horizon-restart:
	$(COMPOSE) exec -T app php artisan horizon:terminate || true

logs:
	$(COMPOSE) logs -f --tail=200

ps:
	$(COMPOSE) ps

stop:
	$(COMPOSE) down
