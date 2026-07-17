# Remove template example tests

Date: 2026-07-16

## Problem

The default Laravel `ExampleTest` files remained in the suite. They asserted that `true` is true and that the root page returns HTTP 200, but they did not verify an AutoERP business, security, architecture, or deployment contract.

These tests inflated the suite count and reduced signal quality.

## Correction

Removed:

- `tests/Unit/ExampleTest.php`
- `tests/Feature/ExampleTest.php`

Project-specific bootstrap, API, frontend, authorization, and module tests remain responsible for real smoke coverage.

## Relationships

No production code, schema, or relationship changed.

## Verification

Run:

```bash
git diff --check
php artisan test
composer test:mysql
```
