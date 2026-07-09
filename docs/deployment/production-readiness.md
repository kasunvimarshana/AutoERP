# AutoERP production readiness

This guide defines the minimum gate before promoting AutoERP to production. It is intentionally explicit so production readiness is verified instead of assumed.

## Environment baseline

Start from `.env.production.example`, then set deployment-specific secrets and hosts.

Required production values:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_KEY` generated with enough entropy for Auth token derivation
- `APP_URL` and `PLATFORM_PUBLIC_URL` set to the public HTTPS origin
- `AUTH_TENANT_REFRESH_COOKIE_SECURE=true`
- `AUTH_PLATFORM_REFRESH_COOKIE_SECURE=true`
- `TENANT_LOCAL_FALLBACK_ENABLED=false`
- `SESSION_ENCRYPT=true`
- `LOG_LEVEL=warning` or stricter
- production database credentials
- production mail credentials
- production queue worker and scheduler configuration

Never deploy with local development defaults such as debug mode, local tenant fallback, insecure cookies, or empty database credentials.

## Runtime readiness command

Run this in the release environment to fail fast on unsafe production configuration:

```bash
php artisan production:readiness --no-interaction
```

The command validates production environment, debug mode, application key, public HTTPS URLs, secure refresh cookies, tenant fallback, database/cache/queue/session drivers, encrypted sessions, private document disks, and log level.

## Verification command

Run the source and runtime gate before every production promotion:

```bash
composer production:verify
```

This command clears cached config, verifies Auth readiness, runs the production readiness command, lists routes, runs the backend test suite, runs frontend typecheck/lint, and builds the frontend bundle.

## Database and permissions gate

Before switching traffic to a new release:

```bash
php artisan migrate --force
php artisan platform:permissions:sync --no-interaction
php artisan auth:readiness --no-interaction
php artisan production:readiness --no-interaction
```

Run these against the same environment and database used by the release candidate.

## Runtime processes

Production needs the following long-running processes managed by the host supervisor or container orchestrator:

- HTTP/PHP-FPM process
- queue worker for `QUEUE_CONNECTION=database`
- scheduler process for Laravel scheduled commands
- Reverb or broadcast process when realtime features are enabled

Each process must use the same release build and production environment variables.

## Storage and private files

Tenant and Vehicle Service documents must use private disks. Public web roots must not expose tenant-private or vehicle-service document storage paths directly.

Required environment keys:

- `TENANT_DOCUMENT_DISK=tenant_private`
- `VEHICLE_SERVICE_DOCUMENT_DISK=tenant_private`
- `PRIVATE_OBJECT_DEFAULT_DISK=tenant_private`

## Release checks

A release is not production-ready until all of these pass:

- `composer production:verify`
- `php artisan migrate --force` on a production-like database
- `php artisan platform:permissions:sync --no-interaction`
- smoke test login, tenant selection, key module navigation, create/update flows, posting/invoice/payment flows, document upload/download, and report export
- queue worker processes jobs successfully
- scheduler runs without errors
- logs show no new critical or error entries during smoke testing

## Rollback expectations

A release must be rollback-safe before traffic is switched:

- database backup or snapshot exists
- previous application release artifact remains available
- environment variables are versioned in the deployment platform
- migration impact is reviewed before production execution

Do not mark a release production-ready when runtime verification is unavailable.
