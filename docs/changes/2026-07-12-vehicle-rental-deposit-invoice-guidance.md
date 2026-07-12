# Vehicle Rental deposit invoice guidance

Date: 2026-07-12

## Evidence

The deposit application and forfeiture backend already validates tenant, organization, customer, currency, invoice lifecycle, positive remaining balance, deposit balance, payment balance, and concurrency versions. The Vehicle Rental UI, however, requested every outbound rental invoice for the selected customer. It could therefore present settled invoices or invoices in another currency and rely on the backend to reject the user's selection.

The project UI rules require relationship choices to be human-readable and constrained to valid foreign-key candidates. The backend must remain the source of truth, while the frontend should prevent blind invalid selections.

## Correction

- Added Invoice-owned `currency_id` and `settlement_eligible` list filters. Currency filtering accepts historical inactive currencies because deactivation blocks new documents, not settlement of existing documents.
- Exposed the canonical settleable invoice statuses from `InvoiceStatusService`; the same list is used by settlement validation and the list query.
- Settlement-eligible queries require a positive authoritative `invoice_balances.remaining_amount`.
- Extended the Vehicle Rental invoice lookup to send currency and settlement-eligibility filters.
- Deposit application and forfeiture now request only outbound rental invoices for the selected customer, in the deposit currency, with an open settleable balance.
- The invoice selector remains disabled until both customer and currency relationships are available.

## Scope

No deposit accounting, payment allocation, invoice status transition, balance calculation, tax rule, route, or API response shape changed. Vehicle Rental does not duplicate Invoice settlement rules.

## Verification

- Modified PHP files pass syntax validation.
- Added an HTTP feature test covering status, positive balance, currency, party, tenant, and organization filtering.
- Added a frontend component test confirming the deposit lookup sends the authoritative filter set.
- Full SQLite, MySQL/MariaDB, TypeScript, ESLint, production-build, and frontend test gates must be rerun from the resulting branch head before release approval.
