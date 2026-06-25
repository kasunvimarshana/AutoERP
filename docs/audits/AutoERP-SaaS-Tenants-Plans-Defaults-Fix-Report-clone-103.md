# AutoERP SaaS Tenants, Tenant Plans, and Platform Defaults Fix Report — clone-103

## Executive verdict

The frontend and the directly responsible backend contracts for **SaaS Tenants**, **Tenant Plans**, and **Platform Defaults** were corrected end-to-end. The original audit contained **76 findings**; all 76 have a root-cause correction in this package.

This is not a cosmetic patch. The activation screenshot exposed a broken contract and workflow:

```text
Backend readiness details → error.context
Frontend parser            → error.details only
Result                     → exact blockers discarded
```

The package standardizes the contract on `error.details`, retains typed error details, renders the exact readiness blockers beside the activation step, and prevents activation until the authoritative readiness endpoint reports success.

## Finding disposition

| Area | Findings fixed |
|---|---:|
| Cross-cutting | 8 |
| SaaS Tenants | 28 |
| Tenant Plans | 18 |
| Platform Defaults | 22 |
| **Total** | **76** |

| Severity | Count |
|---|---:|
| Critical | 6 |
| High | 29 |
| Medium | 41 |

The per-finding correction matrix is available in `AutoERP-SaaS-Tenants-Plans-Defaults-Fix-Matrix-clone-103.csv`.

## 1. Shared error and workflow foundation

- Replaced Tenant-specific `error.context` responses with the canonical shared API error response contract.
- `ApiError` now retains typed `details`.
- Load errors, mutation errors, field validation, and child-panel errors are independent.
- Added contextual success feedback.
- Refreshes retain existing data instead of collapsing the current screen.
- Search, page, filters, and selected tenant state are URL-persisted.
- Added shared copy and success-feedback controls.
- Added focused contract and workflow tests.

## 2. SaaS Tenants

The page now follows a guided operational sequence:

```text
Tenant directory
→ Identity
→ Foundation provisioning
→ Verified primary domain
→ Subscription
→ Final readiness review
→ Activate
```

### Activation and readiness

- Activate exists only in the final readiness step.
- It is disabled until the backend readiness endpoint reports ready.
- Exact blockers are shown with curated labels and the stage responsible for fixing them.
- Activation errors no longer remove the selected tenant details.
- Lifecycle reasons have clear audit guidance and confirmation impact.

### Tenant directory and identity

- Creation is an explicit collapsed workflow rather than a permanently open form.
- The selected tenant is URL-persisted.
- Tenant cards show operational context: lifecycle, foundation, primary domain, and subscription.
- Server field validation is rendered beside the relevant field.
- Logo preview, validation, replacement, and removal are supported.
- Existing inactive currency references remain visible for historical edits; a new currency selection must be active.
- Unsaved tenant edits are protected.

### Foundation and invitation

- Foundation progress no longer mixes domain and subscription blockers.
- Provision/Resume/Retry labels follow the real state.
- The one-time invitation token has copy, expiry, warning, and acknowledgement controls.

### Domains

- Hostnames are semantically validated before the request.
- DNS verification cannot run before a current challenge is requested.
- DNS host/value copy controls and formatted expiry are included.
- Primary, disable, and delete actions have explicit impact confirmation.
- Archived tenants do not expose invalid mutation controls.
- Duplicate reload chains were removed.

### Subscriptions

- Operators select an exact immutable plan revision, not only the latest.
- Current and proposed price, dates, modules, limits, and usage are compared.
- An authoritative impact/readiness check is required before assignment.
- Archived tenants cannot mutate subscriptions.

## 3. Tenant Plans

Plan identity, immutable revisions, and lifecycle are now separate responsibilities.

- Blank `effective_at` is omitted from sparse updates.
- Identity-only updates do not accidentally create revisions.
- `is_active` was removed from the editor.
- Activate and deactivate are explicit lifecycle services/routes/actions.
- Immutable revision history is exposed through API and UI.
- The editor shows exact commercial changes before submission.
- Modules are grouped by business area.
- Limits are displayed as labelled values.
- Deactivation shows assignment/subscription impact.
- Active/inactive filtering and assignment counts are available.
- Historical inactive currency remains visible, but creating a new revision requires an active currency.
- Unsaved plan edits are protected.
- Success and error feedback are contextual.

## 4. Platform Defaults

Platform Defaults now explains inheritance and enforces least privilege.

```text
Organization Unit → Tenant → Global → Definition Default
```

- Added separate platform configuration view permission.
- Manage permission controls normal writes.
- Secret permission separately controls protected replacements.
- Sensitive values remain replacement-only and are never disclosed.
- Duplicate prevention uses server-provided complete existing-key state, not the current page only.
- Each row shows override, effective value, inherited value, source, owner, sensitivity, mutability, configured state, and update time.
- Removing an override previews the exact next effective value/source.
- Search and owner filters are debounced and URL-persisted.
- Table data remains visible during refresh.
- Decimal strings retain exact precision.
- Typed list editors replace raw JSON for normal workflows.
- Conflicts reload authoritative state.
- All actions lock during a mutation.
- Responsive actions are explicit on mobile.
- View-only operators can inspect defaults without receiving write controls.

## 5. Verification

| Check | Result |
|---|---|
| TypeScript typecheck | Passed — 0 diagnostics |
| ESLint | Passed — 0 errors / 0 warnings |
| Frontend tests | Passed — **37 files / 138 tests** |
| Newly added targeted tests | **6 files / 17 tests** |
| Production build | Passed |
| Vite modules transformed | **623** |
| Main JS bundle | **454.03 kB / 140.36 kB gzip** |
| Production npm dependencies | **0 vulnerabilities** |
| Full npm audit | 1 low-severity development-only `esbuild` advisory |
| Changed PHP syntax | Passed — **34 files** |
| Missing internal PHP imports/symbols | **0** |
| `git diff --check` | Passed |
| Target TODO/FIXME/HACK/debug scan | Clean |
| Target raw-ID input/label scan | No user-facing raw foreign-key entry controls |
| Changed/new project files | **66** |

All 37 test files were executed in isolated deterministic shards because the repository's single-process all-suite Vitest command does not terminate reliably after completing tests. Every file and all 138 assertions passed. One existing Vehicle test emits non-fatal React `act(...)` warnings outside this audit scope; the audited screens are clean.

## 6. Backend runtime limitation

The uploaded snapshot did not contain `vendor/`, and Composer was not installed. Therefore this environment could not execute:

- Laravel application boot;
- Artisan route listing;
- migrations and seeders;
- backend PHPUnit;
- real API/database/browser E2E tests.

The changed PHP files were syntax-checked and their internal imports were verified, but release still requires a normal Laravel runtime environment.

## 7. Mandatory release gates

1. Install locked Composer dependencies and boot the Laravel application.
2. Run fresh migrations/seeders against the supported database engine.
3. Run backend PHPUnit and `artisan route:list`.
4. Exercise these three workflows against a migrated multi-tenant database.
5. Verify browser E2E for:
   - blocked and successful tenant activation;
   - DNS challenge/verification;
   - exact plan revision assignment and downgrade impact;
   - plan lifecycle and revision history;
   - read-only configuration operator;
   - configuration manager without secret permission;
   - secret manager replacement flow.
6. Investigate the repository-wide single-process Vitest shutdown/open-handle issue separately.
7. Verify the low-severity development `esbuild` update before changing the lockfile.

## 8. Migration and compatibility note

The changes deliberately correct the current design instead of preserving flawed frontend contracts or plan lifecycle behavior. They do not add compatibility aliases for the removed plan-delete flow or the old Tenant error payload. API consumers must use the canonical versioned contracts in this package.
