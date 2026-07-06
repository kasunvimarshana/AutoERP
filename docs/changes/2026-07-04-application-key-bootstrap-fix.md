# Application key bootstrap fix

Date: 2026-07-04

## Problem

Laravel booted successfully under PHP 8.3, but browser requests failed with `Illuminate\Encryption\MissingAppKeyException` because `.env` had an empty `APP_KEY`.

Without an application encryption key, Laravel cannot safely encrypt cookies, sessions, and other protected payloads.

## Correction

Generated a new Laravel application key in the local environment using the project's PHP 8.3 runtime and cleared the configuration cache so the running app picks up the new value immediately.

## Verification

- Confirmed `.env` now contains a non-empty `APP_KEY`
- `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan key:generate --force`
- `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan config:clear`
