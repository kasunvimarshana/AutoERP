# WhatsApp Verification List and Create UX

Date: 2026-09-11

## Summary

Extended the API-free manual WhatsApp verification workflow so verification state is visible during day-to-day customer and supplier work and can be started immediately after creating a record.

## Backend

- Added Customer- and Supplier-owned verification history relationships.
- Added batched list-state enrichment for customer and supplier index responses without per-row queries.
- List summaries expose the selected WhatsApp recipient and its current manual verification state only when requested by the full list endpoints, preserving lean lookup responses.
- Recipient selection remains centralized in each owning module and follows the existing primary-contact/mobile fallback rules.

## Frontend

- Customer and Supplier contact columns now show the selected WhatsApp number with an accessible status indicator for verified, pending, changed, or unverified state.
- Customer and Supplier create pages offer an optional `Create & verify WhatsApp` flow when a usable number and the required update permission are available.
- The create flow saves the business record first, starts the challenge, and opens WhatsApp. If WhatsApp verification cannot start, the created record remains saved and the user receives an explicit error before continuing to its detail page.
- The vehicle-service quick customer flow now offers the same opt-in behavior. After customer and vehicle creation it presents the verification panel, while allowing the employee to skip and continue to the job.
- Supplier mobile input is labelled `WhatsApp Number` for consistent intent.

## Verification

- `php artisan test tests/Feature/ManualWhatsAppVerificationTest.php`: 2 tests passed, 20 assertions.
- `php artisan test tests/Feature/Customer/CustomerListCurrentVehicleTest.php`: 1 test passed, 14 assertions.
- Focused Supplier API resource test: 1 test passed, 16 assertions.
- `npm run test -- resources/js/shared/components/WhatsAppContactCell.test.tsx`: 2 tests passed.
- `npm run typecheck`: passed.
- `npm run build`: passed.
- `git diff --check`: passed (line-ending warnings only).
