.PHONY: up down build restart logs ps migrate seed test install health

## Start all services
up:
	docker-compose up -d

## Stop all services
down:
	docker-compose down

## Build all services
build:
	docker-compose build

## Restart all services
restart:
	docker-compose restart

## Show logs for all services
logs:
	docker-compose logs -f

## Show running containers
ps:
	docker-compose ps

## Run migrations for all services
migrate:
	docker-compose exec auth-service php artisan migrate --force
	docker-compose exec inventory-service php artisan migrate --force
	docker-compose exec order-service php artisan migrate --force
	docker-compose exec notification-service php artisan migrate --force

## Run seeders for all services
seed:
	docker-compose exec auth-service php artisan db:seed --force
	docker-compose exec inventory-service php artisan db:seed --force
	docker-compose exec order-service php artisan db:seed --force

## Install dependencies for all services
install:
	docker-compose exec auth-service composer install
	docker-compose exec inventory-service composer install
	docker-compose exec order-service composer install
	docker-compose exec notification-service composer install
	docker-compose exec saga-orchestrator composer install
	docker-compose exec api-gateway npm install

## Run tests for all services
test:
	docker-compose exec auth-service php artisan test
	docker-compose exec inventory-service php artisan test
	docker-compose exec order-service php artisan test

## Health check all services
health:
	curl -s http://localhost:8001/api/health | jq .
	curl -s http://localhost:8002/api/health | jq .
	curl -s http://localhost:8003/api/health | jq .
	curl -s http://localhost:8004/api/health | jq .
	curl -s http://localhost:8005/api/health | jq .
