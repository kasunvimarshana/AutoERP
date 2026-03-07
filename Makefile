# ──────────────────────────────────────────────────────────────────────────────
# SaaS Multi-Tenant Inventory Management – Makefile
# ──────────────────────────────────────────────────────────────────────────────

.PHONY: help setup up down logs migrate seed test \
        shell-auth shell-inventory shell-order shell-notification shell-gateway

COMPOSE = docker compose

help: ## Show this help message
@grep -E '^[a-zA-Z_-]+:.*##' $(MAKEFILE_LIST) | \
awk 'BEGIN {FS = ":.*##"}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

setup: ## Copy .env.example files and generate Laravel app keys
@echo "Setting up environment files..."
@cp -n .env.example .env 2>/dev/null || true
@for service in auth-service inventory-service order-service api-gateway; do \
cp -n $$service/.env.example $$service/.env 2>/dev/null || true; \
done
@cp -n notification-service/.env.example notification-service/.env 2>/dev/null || true
@echo "Done. Review .env files before starting."

up: ## Start all services in detached mode
$(COMPOSE) up -d --build

down: ## Stop and remove all containers
$(COMPOSE) down

restart: ## Restart all services
$(COMPOSE) restart

logs: ## Tail logs from all services
$(COMPOSE) logs -f

logs-auth: ## Tail auth service logs
$(COMPOSE) logs -f auth_service

logs-inventory: ## Tail inventory service logs
$(COMPOSE) logs -f inventory_service

logs-order: ## Tail order service logs
$(COMPOSE) logs -f order_service

logs-notification: ## Tail notification service logs
$(COMPOSE) logs -f notification_service

migrate: ## Run migrations for all Laravel services
$(COMPOSE) exec auth_service      php artisan migrate --force
$(COMPOSE) exec inventory_service php artisan migrate --force
$(COMPOSE) exec order_service     php artisan migrate --force
$(COMPOSE) exec auth_service      php artisan passport:install --force

seed: ## Run database seeders for all Laravel services
$(COMPOSE) exec auth_service      php artisan db:seed --force
$(COMPOSE) exec inventory_service php artisan db:seed --force

migrate-fresh: ## Drop and recreate all tables
$(COMPOSE) exec auth_service      php artisan migrate:fresh --seed --force
$(COMPOSE) exec inventory_service php artisan migrate:fresh --seed --force
$(COMPOSE) exec order_service     php artisan migrate:fresh --force

test: test-auth test-inventory test-order test-notification test-frontend ## Run all tests

test-auth: ## Run auth service tests
$(COMPOSE) exec auth_service php artisan test

test-inventory: ## Run inventory service tests
$(COMPOSE) exec inventory_service php artisan test

test-order: ## Run order service tests
$(COMPOSE) exec order_service php artisan test

test-notification: ## Run notification service tests (Jest)
$(COMPOSE) exec notification_service npm test

test-frontend: ## Run frontend tests (Vitest)
cd frontend && npm test

shell-auth: ## Open shell in auth service container
$(COMPOSE) exec auth_service sh

shell-inventory: ## Open shell in inventory service container
$(COMPOSE) exec inventory_service sh

shell-order: ## Open shell in order service container
$(COMPOSE) exec order_service sh

shell-notification: ## Open shell in notification service container
$(COMPOSE) exec notification_service sh

shell-gateway: ## Open shell in api-gateway container
$(COMPOSE) exec api_gateway sh
