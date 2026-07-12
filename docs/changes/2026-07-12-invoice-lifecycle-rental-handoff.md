# Invoice lifecycle and Vehicle Rental document handoff

Date: 2026-07-12

## Evidence

Vehicle Rental calculation runs could generate an outbound lessee invoice or inbound owner payable, but the billing page discarded the returned invoice identifier. The shared Invoice detail page exposed print and PDF actions only; it did not expose the existing backend approve, post, or cancel commands. A newly generated draft therefore had no guided path to reach a posted financial state.

## Correction

- Added typed Invoice API functions for approve, post, and cancel commands using the loaded row version.
- Added permission-gated lifecycle actions to Invoice detail:
  - draft to approved;
  - approved to posted;
  - draft or approved to cancelled with an optional reason.
- Successful commands replace the loaded resource with the authoritative backend response and refresh the balance view when available.
- Vehicle Rental billing now retains the generated invoice response and navigates directly to its Invoice-owned detail workspace.
- Rental-origin invoice detail provides a clear route back to Rental Billing.

## Scope

No invoice state machine, route, financial calculation, tax posting, balance rule, rental calculation, or database schema changed. The frontend now exposes backend-owned commands that already existed and remained the source of truth.

## Verification

- Modified TypeScript files pass transpilation checks.
- A frontend contract test verifies row-version-aware lifecycle calls, permission gates, and the Rental Billing document handoff.
- Full TypeScript, ESLint, Vite, frontend, SQLite, and MySQL/MariaDB test gates must be rerun from the resulting branch head before release approval.
