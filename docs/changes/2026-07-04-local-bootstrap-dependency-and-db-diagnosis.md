# Local bootstrap dependency and database diagnosis

Date: 2026-07-04

## Problem

Running `php artisan migrate` failed before Laravel could boot because the repository was missing the Composer-managed `vendor` directory and `vendor/autoload.php`.

After restoring Composer dependencies, the default CLI still failed because Laragon was using PHP 8.1.10 while this project's `composer.json` and lockfile require PHP 8.2 or newer. Running the command under PHP 8.3 then exposed a separate environment configuration issue: `.env` selected the `sqlite` connection while `DB_DATABASE` was set to `autodb`, which is not a valid existing SQLite database file path.

## Correction

Installed Composer dependencies with the local PHP 8.3 binary so `vendor/autoload.php` and the optimized autoloader were generated successfully.

Verified the remaining blockers are environmental rather than application-code defects:

- CLI PHP version must be switched from 8.1.10 to 8.3.x for normal `php artisan` usage.
- Database configuration in `.env` must be aligned with the intended engine by either using a real SQLite file path or switching to the correct MySQL connection values.

## Verification

- `composer install` with PHP 8.1.10 failed with platform requirement errors for `php ^8.2`.
- `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe C:\laragon\bin\composer\composer.phar install`
- `php artisan migrate` now fails with Composer's PHP platform check instead of missing `vendor/autoload.php`.
- `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan migrate --no-interaction` now reaches Laravel and fails on the configured SQLite database path `autodb`.
