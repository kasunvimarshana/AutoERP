# Configuration Module

## Purpose

This module provides a unified configuration layer for API and CLI usage with a single service contract.

## Architecture

- Domain: configuration entity and normalization/serialization rules.
- Application: DTOs, repository contract, service orchestration, Core `Result` usage.
- Infrastructure: Eloquent repository, migration, cache/env/runtime adapters, provider wiring.
- Interface: REST controller/resources/requests and Artisan commands.

## Core Alignment

- Uses `RepositoryPortInterface` through `ConfigurationRepositoryInterface`.
- Uses domain `Entity` for configuration objects.
- Uses DTOs for API/CLI communication.
- Uses Core `Result` and `Error` for operation outcomes.
- No Eloquent usage outside Infrastructure.

## API Endpoints

- `GET /api/configuration/entries`
- `GET /api/configuration/entries/{key}`
- `POST /api/configuration/entries`
- `PUT /api/configuration/entries/{key}`
- `POST /api/configuration/cache/clear`
- `POST /api/configuration/reload`

## CLI Commands

- `php artisan config:list`
- `php artisan config:get {key}`
- `php artisan config:set {key} {value}`
- `php artisan config:clear-cache`
- `php artisan config:reload`

## Usage Examples

### API: Set

```http
POST /api/configuration/entries
Content-Type: application/json

{
  "key": "inventory.low_stock_threshold",
  "value": 15,
  "source": "database",
  "description": "Low stock alert threshold"
}
```

### API: Get

```http
GET /api/configuration/entries/inventory.low_stock_threshold
```

### CLI: Set

```bash
php artisan config:set inventory.low_stock_threshold 15 --source=database
```

### CLI: List

```bash
php artisan config:list --prefix=inventory.
```
