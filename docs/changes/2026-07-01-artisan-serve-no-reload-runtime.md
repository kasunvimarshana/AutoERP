# Artisan serve no-reload runtime

Date: 2026-07-01

## Problem

The application returned `MissingAppKeyException` from `GET /` on `localhost:8000` even though `.env` contained a valid base64 `APP_KEY`, cached configuration was absent, and `php artisan auth:readiness --no-interaction` passed with an uncached configuration.

## Root cause

Laravel's default `artisan serve` reload mode starts the child PHP development server with most environment variables stripped. In this Windows runtime, that left the child server with a blank application key while normal CLI commands still resolved the key from `.env`.

## Correction

`composer dev` now starts the web server with `php artisan serve --no-reload`, which preserves the validated runtime environment for the child PHP server instead of allowing the web process to resolve a blank key.

The application key was not regenerated or rotated.

## Verification

- `php artisan optimize:clear`
- `php artisan queue:restart`
- `php artisan auth:readiness --no-interaction`
- Started `php artisan serve --host=127.0.0.1 --port=8000 --no-reload`
- Verified `GET http://127.0.0.1:8000/` returns HTTP 200.
