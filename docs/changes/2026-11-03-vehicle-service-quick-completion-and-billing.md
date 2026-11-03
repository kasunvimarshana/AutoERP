# Vehicle Service quick completion and billing

## Request

Let busy staff finish a draft service job without recording inspection and start steps, post the invoice automatically, and continue to payment. Keep the job-line requirement and require a billable line for automatic billing.

## Changes

- Added a dedicated `Complete & bill` action that completes the job and creates, approves, and posts its invoice in one database transaction. It uses the workspace business date, verifies the expected job version, requires an active billable line, and requires both job-transition and invoice-creation permissions.
- Allowed direct draft/inspected-to-completed transitions. The status service still requires an active job line, checks vehicle admission and eligibility, issues reserved stock, and records only the transitions that actually occurred. A failed invoice or validation rolls the whole action back.
- Put the available Inspect, Start work, and Complete & bill actions in the job header. The header shows the invoice total. A successful bill opens the payment screen with the posted invoice selected when an outstanding balance and payment permissions exist; zero-balance bills open the invoice. The existing invoice editor remains available for completed jobs that require separate billing, such as restored invoices after reversal.
- Kept the existing job-line, invoice-source, invoice-link, and payment-link relationships. No schema relationships changed because those links already express the correct ownership and preserve invoice history.

## Verification

- Focused Vehicle Service backend tests: 4 passed, 17 assertions (direct draft completion, reserved stock issue, missing billable line rollback, stale version rejection).
- Frontend job detail tests: 14 passed across 2 files.
- TypeScript typecheck, focused ESLint, PHP syntax checks, and `git diff --check` passed.
