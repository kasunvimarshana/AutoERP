# Reconciliation of uploaded local AutoERP test runs (2026-10-11)

## Verified inputs and branch boundaries
The user supplied two identical captured CLI logs from `D:\\project-new\\AutoERP` with multiple distinct branch snapshots. Do not combine results from different branches as evidence for this PR.

- `worktree`: initial PHP suite 882 pass; several TypeScript errors and unused Input, alongside a Vite build and later test runs. The combined PR already tightens the Agreement currency contract and removes unused Running Charts Input; these original diagnostics are not proof of a current candidate failure.
- `sell-module`: earlier frontend test failures and untracked TACGL staging data.
- **`integration/vehicle-rental-release-candidate-20261011`** (then at GitHub commit `645843f48480379891aa0cf919e9065e606ae895`): `php artisan test` **891 passed, 6 failed**, `npm run typecheck` one TS2322 in Rental base charge pagination, `npm run lint` 1 error / 5 warnings, `npm run build` passed, `npm run test` 419 passed / 7 failed. The original GitHub Actions jobs still failed at 0 steps.
- `worktree-0.0.8`: local `php artisan test` **888 passed**, `npm run lint` passed, `npm run build` passed, `npm run test` **416 passed**; `npm run typecheck` still reported the same Rental base charge pagination type error. These are *not* successful full-suite results on the candidate.

## Confirmed, narrowly corrected source issues on this candidate
1. `VehicleRental/Database/Migrations/...create_vehicle_rental_incident_events_table.php`: restore the required unique composite `[id, tenant_id]` to match the tenant isolation schema contract, for new candidate table.
2. `VehicleRental/Services/RentalIncidentService.php`: replace raw tenant-table event insertion with the existing immutable RentalIncidentEvent model write so the tenant execution guard and append-only model lifecycle apply. Use the model's built-in snapshot JSON cast rather than encoding twice. Existing per-tenant transactional event semantics remain.
3. `resources/js/modules/vehicle-rental/BaseRentBillingPanel.tsx`: normalize nullable `useApi` response to `undefined` only at the `Pagination` optional prop boundary; keep `ChargePage`'s true paginated API contract.
4. `resources/js/modules/reporting/pages/VehicleServiceSalesSummaryReportPage.tsx`: remove unused dead `Amount` component. The active report display components are unchanged.

## Remaining candidate test failures — not silently waived
- **Reporting:** `ReportingFrameworkTest::requested_report_families_share_the_existing_pdf_export_path` PDF response mismatch. Diagnose actual Reporting contract/test against the candidate and fix within Reporting.
- **VehicleService:** three `VehicleServiceEngineTest` scenarios start jobs without active lines, now forbidden by `VehicleServiceStatusService`'s domain invariant. Confirm whether the test fixture needs a valid line without weakening the runtime rule.
- **Frontend:** four `VehicleServiceEmployeeAssignmentTab` tests expected a `Select one or more employees` button that was absent from the rendered state; three `VehicleServicePaymentPreparePage` tests failed. Investigate owning module, mock fixtures and actual UX independently before altering.
- **Lint warnings:** React hook effect warnings in layout, Customer, Selling, and Rental. These were warnings, not failing checks, and need owner-based review.
- **CI:** GitHub issue #124, all jobs 0 steps; do not disable tests.

## Validation statement
This change is source-reconciled against the user's local test output and exact GitHub file content. No app checkout/dependencies or DB test runner is available in this tool runtime. The corrected candidate still requires re-running all suites, migrations, accounting, UI tests and release acceptance. **This is not a production approval, and no base branch was changed.**
