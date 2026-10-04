# Vehicle Rental business-timezone UI finalization — 2026-10-04

## Scope

Fresh end-to-end continuation from the live authoritative `worktree-0.0.8` after the completed Vehicle Rental UI/UX audit.

This pass revalidated:

- current repository/production parity;
- TACGL/video source hashes and TACGL ZIP corpus parity;
- protected backup metadata and static credential evidence;
- Vehicle Rental runtime/schema ancestry and relationship direction;
- current frontend/operator surfaces;
- current external accounting, tax and database-concurrency guidance.

No removed/legacy Rental code was restored, copied or referenced.

Authoritative branch at audit start:

- `worktree-0.0.8`: `c5f54271ce1af3fb8bd1c1bce8d50b3b291d900f`
- tree: `29894b5b6f9d1f55fc7576780d4dd7476c47594a`

Production `worktree` was file-tree identical at audit start.

## Source evidence revalidation

Fresh SHA-256 values matched the registered source corpus:

- `TACGL.zip`: `0e0733fff720072af4c3aaa787995ff128bfa79060a37739d6d2ebbe18a25313`
- `TACGL(20261001-005122).zip`: `79c240494943437978754169c3360bb7c6e35d911ef8263c4b2d6b6246384d77`
- `TACGL.rar`: `0001a91ab4dfdf31d60f669b30f1ccc2da475d7841b2ceea505ef97eff5bedbf`
- `1.mp4`: `ac4ca8e632081c32cd2a1d2e6facb070acf4a1f5304a4dc7a468ca7073b953cf`
- `Recording 2026-06-21 132314.mp4`: `11866d255dbb709055b43bb7428538a3e2f0858a8ee1d0144187bcdaf4616ffa`
- `2.mp4`: `cd2ba1399f149003f19080327458e4bbe4619b88eed9416053c7f8d21431c36f`
- `ScreenVideo_03-04-2026_18-02-52.mp4`: `c9853b7923e7cb95f1014cf598416faa550bfbd56f19da56b613f160d0528ce9`

Both TACGL ZIPs normalize to exactly 452 non-directory business files with identical normalized paths and identical per-file hashes.

## Protected backup

The nested TACGL backup was inspected again non-destructively.

Results:

- 86 archive entries;
- all 86 entries require a password;
- archive has no comment;
- accessible TACGL config/static strings were searched for explicit archive path, WinRAR/RAR command, password-switch and BACKUPM/BACKUPD credential evidence;
- only the bundled WinRAR executable identified itself (`WinRAR 2.90 beta 4`);
- no explicit backup password or password-bearing archive command was found.

Application-user credentials were not reused as archive-password guesses. No brute force, dictionary attack or arbitrary password mutation was performed. The password was not legitimately recovered and remains non-blocking.

## Newly discovered root issue

The AutoERP frontend already has a shared business-timezone source:

- `AuthProvider` configures the business timezone from the active organization unit, falling back to the tenant;
- shared `businessDate` utilities use that configured timezone.

Vehicle Rental bypassed that source by defining:

- a module-load browser timezone through `Intl.DateTimeFormat().resolvedOptions().timeZone`;
- its own browser-local `datetime-local` to offset conversion.

The backend Rental calendar uses Configuration-owned `localization.timezone` for tenant/organization commercial-day decisions.

This created a real cross-layer inconsistency risk: an operator whose browser timezone differs from the configured workspace could see/submit operational wall times under a different timezone than the backend commercial calendar.

## Fix

The fix belongs to the shared business-time utility, not a Rental compatibility patch.

### Shared business-time foundation

`resources/js/shared/utils/businessDate.ts` now owns:

- current configured business-timezone label;
- validated conversion of a business-local `datetime-local` value to an explicit offset timestamp;
- seconds-preserving business-local input formatting.

The conversion:

- keeps the existing browser-local fallback only when no business timezone is configured;
- resolves IANA timezone offsets without a new dependency;
- handles standard and daylight-saving offsets;
- rejects nonexistent local wall times during DST transitions;
- preserves seconds for Running Chart evidence.

Named constants are used for minute/hour conversion and bounded timezone-resolution passes; no raw operational magic values were introduced.

### Rental adoption

Vehicle Rental now delegates `timestampWithOffset`, edit input formatting and timezone labels to the shared configured business-time source.

Read-only operational/audit timestamps now use `formatBusinessDateTime` instead of raw API ISO strings in:

- Vehicle Use current panel;
- Vehicle Use register;
- Vehicle Use immutable history;
- Running Chart current panel;
- Running Chart register;
- Running Chart immutable history;
- Agreement immutable audit-history timestamp.

This is presentation/conversion only. Stored UTC data, API contracts and backend commercial-calendar rules are unchanged.

### Existing backend validation mirrored in UI

No new business rule was introduced.

The UI now mirrors existing server validation by:

- preventing Agreement end dates before the start date;
- constraining Vehicle Use planned return to the planned handover or later while backend still requires it to be strictly later;
- constraining Running Chart end to the usage start or later while backend still requires it to be strictly later;
- keeping Running Chart Save disabled until required reference/start/end and selected driver-identity details are complete.

The backend remains authoritative.

## External-authority re-check

Authoritative current sources were used only to validate architecture boundaries:

- IASB's July 2026 IFRS 16 post-implementation review concluded the standard is overall working as intended; it remains accounting guidance rather than a source for AutoERP operational Rental tariffs.
- Sri Lanka IRD's 2026 material includes revised VAT tax-invoice requirements and current WHT/AIT guidance; statutory treatment remains effective-dated and owned by Tax/Invoice/Payment rather than hardcoded in Rental.
- MySQL 8.4 continues to document locking reads and consistent transaction ordering as appropriate deadlock-reduction controls.

No external source justified a new Rental rate, tax percentage, withholding threshold, free-KM rule, replacement surcharge, downtime deduction or deposit priority.

## Verification performed

### Executed utility verification

The exact new shared business-time algorithm was copied into an isolated TypeScript file and executed with the container's free local tools:

- Node.js `v22.16.0`;
- TypeScript `5.8.3`;
- strict TypeScript compilation passed;
- runtime assertions passed for:
  - Asia/Colombo `+05:30` conversion;
  - UTC instant equivalence;
  - seconds-preserving business-local round-trip;
  - New York winter `-05:00` and summer `-04:00` offsets;
  - rejection of the nonexistent `2026-03-08T02:30` New York DST wall time.

### Branch static gate

The final changed production source was scanned for:

- unused import candidates: none;
- `TODO` / `FIXME` / `HACK` / `XXX`: none;
- Rental-owned browser timezone calls: none;
- raw operational ISO timestamp presentation in changed Rental components: none;
- hardcoded `step={60}`: none;
- backend/schema/migration files: none.

Focused tests were added/updated for:

- shared configured timezone conversion and DST behavior;
- Rental timezone label/outbound timestamp/edit round-trip;
- business-time Vehicle Use register/history display;
- business-time Running Chart register/current display;
- Agreement, Vehicle Use and Running Chart period UI constraints;
- Running Chart required/driver identity completeness guards.

### Full-suite boundary

The latest dependency-backed application baseline remains the previously executed green run:

- Laravel: **881 tests / 9,697 assertions passed**;
- TypeScript typecheck passed;
- ESLint passed with no warnings/errors;
- Vite production build passed;
- Vitest: **101 / 101 files, 374 / 374 tests passed**.

The current environment still has no complete AutoERP checkout/dependency tree and cannot fetch it through GitHub/npm DNS, while hosted GitHub Actions remain excluded by the free-tools-only instruction. Therefore a full post-change dependency-backed application run is not claimed.

## Architecture / relationship result

No Rental backend relationship or schema change is justified.

The existing directional model remains:

- successor Agreement -> predecessor Agreement;
- Vehicle Use -> Customer Agreement / optional Owner Agreement / Vehicle;
- replacement Vehicle Use -> predecessor Vehicle Use;
- Running Chart -> Vehicle Use;
- corrected Running Chart -> predecessor chart;
- Rental charge source -> owner-module financial document.

No redundant inverse relationship, circular Rental dependency, duplicate ledger or copied financial lifecycle was introduced.

## Decision

This pass found and fixed one genuine remaining foundation issue: frontend operational time was not consuming the same configured business timezone already owned by AutoERP configuration and used by the Rental backend calendar.

With that correction, focused coverage and the period-form UX guards in place, there is no known evidence-backed Vehicle Rental requirement left open within the repository boundary.

This record does not claim a live hosting deployment, live production database change or human browser UAT that was not actually executed.
