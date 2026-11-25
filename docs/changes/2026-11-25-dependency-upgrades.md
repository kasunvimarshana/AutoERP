# Dependency Upgrades & Tooling Modernization (Backend & Frontend)

- **Date:** 2026-11-25
- **Scope:** Full-project Composer/PHP and npm/Vite/Vitest dependency upgrades, CI Node runtime alignment, full verification gates, and upgrade/rollback procedures.

---

## 1. Context & Objectives

Safely update and upgrade AutoERP's backend (Composer/PHP) and frontend (npm/Vite/Vitest) dependencies to the latest compatible versions without breaking PHP `^8.2` runtime compatibility or TypeScript/ESLint peer dependencies, verify all quality and security gates, and document all version transitions, configuration changes, deferred majors, and rollback steps.

---

## 2. Modified & Created Files Summary

| File | Change Description |
|---|---|
| `composer.json` | Upgraded `laravel/tinker` constraint from `^2.10.1` to `^3.2`. |
| `composer.lock` | Locked 29 upgraded packages, 1 new polyfill (`symfony/polyfill-php82 v1.43.0`), and 1 removed transitive package (`thecodingmachine/safe v3.4.0`). |
| `package.json` | Updated `devDependencies` constraints for coordinated major frontend tooling: `vite` (`^8.3.8`), `@vitejs/plugin-react` (`^6.3.0`), `laravel-vite-plugin` (`^3.2.1`), `vitest` (`^5.1.2`), `@testing-library/jest-dom` (`^8.0.0`), `jsdom` (`^31.0.1`), `@types/node` (`^26.6.10`), and `concurrently` (`^10.0.7`). |
| `package-lock.json` | Locked Phase 1 minor/patch updates, Phase 2 major tooling upgrades, and pruned extraneous packages. |
| `.github/workflows/laravel.yml` | Updated `frontend` job `node-version` from `"22"` to `"24"` to satisfy `jsdom@31.0.1` engine requirements (`^22.22.2 \|\| ^24.15.0 \|\| >=26.0.0`) and align CI with local Node `v24.22.3`. |
| `docs/changes/2026-11-25-dependency-upgrades.md` | Created this append-only change record. |

---

## 3. Upgraded Packages Inventory

### 3.1 Backend (Composer)
1. **Direct dependency constraint upgrade:**
   - `laravel/tinker`: `v2.11.1` (`^2.10.1`) -> `v3.2.0` (`^3.2`)
2. **Within-constraint updates (`composer update --no-interaction`):**
   - `darkaonline/l5-swagger`: `11.1.0` -> `11.4.1`
   - `laravel/pail`: `v1.2.7` -> `v1.3.0`
   - `laravel/passport`: `v13.7.5` -> `v13.12.0`
   - `laravel/pint`: `v1.29.3` -> `v1.30.4`
   - `laravel/reverb`: `v1.10.2` -> `v1.14.0`
   - `laravel/sail`: `v1.63.0` -> `v1.71.0`
   - `mockery/mockery`: `1.6.12` -> `1.6.15`
   - `nunomaduro/collision`: `v8.9.4` -> `v8.9.6`
   - `phpunit/phpunit`: `11.5.55` -> `11.5.58`
   - `spatie/laravel-pdf`: `2.12.0` -> `2.14.0`
   - Transitive updates: `filp/whoops` (`2.18.4` -> `2.19.3`), `firebase/php-jwt` (`v7.1.0` -> `v7.2.2`), `hamcrest/hamcrest-php` (`v2.1.1` -> `v3.0.0`), `lcobucci/jwt` (`5.6.0` -> `5.6.2`), `league/oauth2-server` (`9.4.1` -> `9.5.0`), `masterminds/html5` (`2.10.1` -> `2.12.0`), `myclabs/deep-copy` (`1.13.4` -> `1.14.0`), `nikic/php-parser` (`v5.8.0` -> `v5.9.0`), `phpseclib/phpseclib` (`3.0.59` -> `4.0.4`), `phpstan/phpdoc-parser` (`2.3.2` -> `2.4.2`), `pusher/pusher-php-server` (`7.2.8` -> `7.3.0`), `radebatz/type-info-extras` (`1.0.7` -> `1.0.16`), `sabberworm/php-css-parser` (`v9.4.0` -> `v9.5.0`), `spatie/laravel-package-tools` (`1.93.1` -> `1.93.3`), `swagger-api/swagger-ui` (`v5.32.8` -> `v5.36.0`), `symfony/psr-http-message-bridge` (`v7.4.8` -> `v7.4.24`), `symfony/type-info` (`v7.4.9` -> `v7.4.24`), `zircote/swagger-php` (`6.3.0` -> `7.10.0`).
   - Added: `symfony/polyfill-php82` (`v1.43.0`)
   - Removed: `thecodingmachine/safe` (`v3.4.0`)

### 3.2 Frontend (npm)
1. **Phase 1 — Safe Minor & Patch Updates:**
   - `react` & `react-dom`: `19.2.7` -> `19.3.1`
   - `react-router-dom`: `7.18.4` -> `7.18.7`
   - `react-toastify`: `11.1.0` -> `11.2.0`
   - `axios`: `1.20.0` -> `1.21.1`
   - `zustand`: `5.0.14` -> `5.1.0`
   - `@tailwindcss/vite` & `tailwindcss`: `4.3.2` -> `4.3.5`
   - `@types/react`: `19.2.17` -> `19.3.3`
   - `@types/react-dom`: `19.2.3` -> `19.3.0`
   - `@testing-library/react`: `16.3.2` -> `16.3.3`
   - `@testing-library/user-event`: `14.6.1` -> `14.6.13`
   - `eslint`: `10.14.0` -> `10.15.0`
   - `typescript-eslint`: `8.72.1` -> `8.74.0`
   - `globals`: `17.7.0` -> `17.14.0`
2. **Phase 2 — Coordinated Major Tooling Upgrades:**
   - `vite`: `7.3.7` -> `8.3.8`
   - `@vitejs/plugin-react`: `5.2.0` -> `6.3.0`
   - `laravel-vite-plugin`: `2.1.0` -> `3.2.1`
   - `vitest`: `4.1.12` -> `5.1.2`
   - `@testing-library/jest-dom`: `6.9.1` -> `8.0.0`
   - `jsdom`: `29.1.1` -> `31.0.1`
   - `@types/node`: `25.9.4` -> `26.6.10`
   - `concurrently`: `9.2.5` -> `10.0.7`

### 3.3 Configuration Notes
- `vite.config.ts`: Inspected `build.rollupOptions.output.manualChunks` (`splitVendorChunk`) under Vite `8.3.8` (Rolldown). Both `tsc --noEmit` and `vite build` completed with 0 errors and 0 deprecation warnings; no `vite.config.ts` changes were required.
- `.github/workflows/laravel.yml`: Updated `frontend` job `node-version` from `"22"` to `"24"` to guarantee satisfaction of `jsdom@31.0.1` (`"node": "^22.22.2 || ^24.15.0 || >=26.0.0"`).

---

## 4. Deferred Major Upgrades

- **`laravel/framework` (`^13.0`) & `phpunit/phpunit` (`^12.0`):** Deferred because both require PHP `^8.3`, whereas `composer.json` targets `"php": "^8.2"` and the local runtime is PHP `8.2.12`.
- **`typescript` (`7.0.2`):** Deferred because `typescript-eslint@8.74.0` has a peer dependency constraint of `>=4.8.4 <6.1.0`.

---

## 5. Verification & Quality Gate Results

| Gate / Command | Result | Observed Output |
|---|---|---|
| `composer audit --locked --no-interaction` | **PASS** | `No security vulnerability advisories found.` |
| `npm audit` | **PASS** | `found 0 vulnerabilities` |
| `php artisan test` (SQLite) | **PASS** | `1429 passed (15071 assertions)` in `401.44s` |
| `php vendor/bin/phpunit --configuration phpunit.mysql.xml` (MySQL `autoerp_test`) | **PARTIAL (4 pre-existing failures)** | `Tests: 1429, Assertions: 15023, Errors: 2, Failures: 2` (`VehicleBlockerTest::test_unreferenced_vehicle_can_be_deleted`, `TwoStageInvoicingTest::test_attach_running_charts_aggregates_tolls_from_rental_expense`, `VehicleRentalQuotationPreviewTest`, `RentalQuotationPreviewTest` — pre-existing schema/fixture mismatches on MySQL, unrelated to dependency upgrades; note `composer test:mysql` hits Composer's 300s default process timeout unless invoked directly via `vendor/bin/phpunit` or with `COMPOSER_PROCESS_TIMEOUT=0`). |
| `php artisan auth:readiness --no-interaction` | **PASS** | `13/13` checks `READY` (`Auth readiness passed.`) |
| `php artisan platform:health --no-interaction` | **PASS** | `mail`, `queue`, `migrations` (`pending_count=0`) all `READY` |
| `php artisan route:list --path=api/documentation` | **PASS** | `1` route (`l5-swagger.default.api`) registered |
| `php artisan route:list --path=oauth` | **PASS** | `13` routes (`l5-swagger` + `passport` + `TenantOAuthController`) registered |
| `php vendor/bin/pint --test --dirty` | **PASS** | `{"tool":"pint","result":"passed"}` |
| `npm run typecheck` | **PASS** | `0` errors (`tsc --noEmit`) |
| `npm run lint` | **PASS** | `0` errors (`eslint resources/js`) |
| `npm test` (Vitest `v5.1.2`) | **PASS** | `177 passed (177)` test files, `745 passed (745)` tests in `306.50s` |
| `npm run build` (Vite `v8.3.8`) | **PASS** | `790 modules transformed`, clean production build in `2.04s` |

---

## 6. Upgrade & Rollback Procedures

### Repeatable Upgrade Steps
1. Backend: `composer update --no-interaction` followed by `composer update laravel/tinker --no-interaction` (with `"laravel/tinker": "^3.2"` in `composer.json`).
2. Frontend Phase 1: `npm update` on runtime/dev minor/patch packages.
3. Frontend Phase 2: `npm install -D "vite@^8.3.8" "@vitejs/plugin-react@^6.3.0" "laravel-vite-plugin@^3.2.1" "vitest@^5.1.2" "@testing-library/jest-dom@^8.0.0" "jsdom@^31.0.1" "@types/node@^26.6.10" "concurrently@^10.0.7"`.
4. Verify all gates (`composer audit --locked`, `npm audit`, `php artisan test`, `npm run typecheck`, `npm run lint`, `npm test`, `npm run build`).

### Rollback Procedure
1. Restore lockfiles and manifests from the previous commit:
   `git checkout HEAD -- composer.json composer.lock package.json package-lock.json .github/workflows/laravel.yml`
2. Reinstall pinned dependencies:
   `composer install --no-interaction && npm ci`
3. Rebuild assets and re-verify:
   `npm run build && php artisan test && npm test`

---

## Author & Maintainer

- **Lead Architect & Developer:** Kasun Vimarshana
- **Email:** kasunvmail@gmail.com
- **Phone:** +94713525253
- **Repository:** https://github.com/kasunvimarshana/AutoERP
