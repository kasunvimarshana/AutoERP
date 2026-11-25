# Composer Test Script Process Timeout Fix

- **Date:** 2026-11-25
- **Scope:** Disable Composer's default 300s process timeout on `composer test` and `composer test:mysql` scripts.

---

## 1. Context & Objectives

Full backend test runs (`php artisan test` on SQLite: ~358s–401s; `phpunit.mysql.xml` on MySQL: ~388s) exceed Composer's default 300-second process timeout, causing `composer test` and `composer test:mysql` to abort prematurely on slower/Windows environments. Adding `Composer\Config::disableProcessTimeout` to both scripts (matching the existing `dev` script) resolves this.

---

## 2. Modified & Created Files Summary

| File | Change Description |
|---|---|
| `composer.json` | Added `"Composer\\Config::disableProcessTimeout"` to `scripts.test` and `scripts.test:mysql`. |
| `docs/changes/2026-11-25-composer-test-timeout-fix.md` | Created this append-only change record. |

---

## 3. Verification & Quality Gate Results

| Gate / Command | Result | Observed Output |
|---|---|---|
| `composer validate --strict --no-interaction` | **PASS** | `./composer.json is valid` |
| `composer test` (SQLite) | **PASS** | `1429 passed (15071 assertions)` in `358.29s` (ran >300s without timing out) |

---

## Author & Maintainer

- **Lead Architect & Developer:** Kasun Vimarshana
- **Email:** kasunvmail@gmail.com
- **Phone:** +94713525253
- **Repository:** https://github.com/kasunvimarshana/AutoERP
