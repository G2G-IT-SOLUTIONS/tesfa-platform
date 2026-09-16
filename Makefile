.PHONY: help up down restart logs shell migrate fresh seed build dev prod artisan composer npm test

# ==========================================================
# Tesfa Platform — Docker shortcuts
# ==========================================================

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'

# ----------------------------------------------------------
# Lifecycle
# ----------------------------------------------------------

up: ## Start all services (dev mode)
	docker compose up -d

down: ## Stop all services
	docker compose down

restart: ## Restart all services
	docker compose restart

build: ## Rebuild images
	docker compose build --no-cache

logs: ## Tail logs (all services)
	docker compose logs -f

logs-app: ## Tail app logs
	docker compose logs -f app

# ----------------------------------------------------------
# Laravel
# ----------------------------------------------------------

shell: ## Open shell in app container
	docker compose exec app sh

artisan: ## Run artisan (usage: make artisan CMD="route:list")
	docker compose exec app php artisan $(CMD)

composer: ## Run composer (usage: make composer CMD="require foo/bar")
	docker compose exec app composer $(CMD)

npm: ## Run npm (usage: make npm CMD="install")
	docker compose exec app npm $(CMD)

migrate: ## Run migrations
	docker compose exec app php artisan migrate

fresh: ## Drop all tables and re-migrate
	docker compose exec app php artisan migrate:fresh

seed: ## Seed database
	docker compose exec app php artisan db:seed

test: ## Run tests
	docker compose exec app php artisan test

# ----------------------------------------------------------
# Frontend
# ----------------------------------------------------------

dev: ## Run Vite dev server on host
	npm run dev

build-assets: ## Build frontend assets
	docker compose exec app npm run build

# ----------------------------------------------------------
# Database
# ----------------------------------------------------------

db-shell: ## Open MySQL shell
	docker compose exec mysql mysql -utesfa_user -pchange_me tesfa_platform

db-dump: ## Dump database to backups/
	docker compose exec mysql mysqldump -utesfa_user -pchange_me tesfa_platform | gzip > storage/app/backups/dump-$$(date +%Y%m%d_%H%M%S).sql.gz

redis-shell: ## Open Redis CLI
	docker compose exec redis redis-cli

# ----------------------------------------------------------
# Production
# ----------------------------------------------------------

prod: ## Start in production mode
	docker compose -f docker-compose.yml up -d

prod-build: ## Build production image
	docker compose -f docker-compose.yml build --no-cache
