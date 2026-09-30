# A5 invoice layout and service invoice print-copy footer

## Summary

- Added an Invoice-owned print-layout setting with `A4 Standard (Portrait)` and `A5 Compact (Landscape)` choices.
- Added the setting to the active organization unit's **Document settings** panel.
- Added a compact A5 invoice template that preserves the existing invoice data and fits on one landscape A5 page.
- Added service-invoice print-copy tracking using the nullable `invoices.original_printed_at` column.
- Kept the user-facing action labelled **Print** and added copy information only to the printed footer.

## Print-copy behaviour

- The first issued Service Invoice print is labelled `Original` in the footer.
- Later issued prints are labelled `Duplicate` in the footer.
- The footer format is `Printed: <date/time> | By: <user> | Original|Duplicate`.
- Copy state is issued atomically inside a database transaction with a row lock so concurrent print requests cannot both become the original.
- Signed print URLs carry server-issued print context; unsigned requests cannot supply trusted copy metadata.
- WhatsApp document sharing does not consume or change the Original/Duplicate print state.

## Configuration and ownership

- The setting key is owned and registered by the Invoice module: `invoice.default_print_layout`.
- The default remains `standard_a4`; users can select `compact_a5` from **Administration > Organization Units > Document settings** for the active unit.
- Paper dimensions, orientation, CSS class, and copy labels are represented by Invoice enums instead of scattered literals.

## Verification

- Laravel focused tests: 10 passed, 98 assertions.
- PHP Pint check passed for the modified Invoice PHP files.
- Frontend ESLint passed for the changed configuration and organization-unit files.
- Production frontend build passed (668 modules transformed).
- TypeScript's full-project check still reports the pre-existing unrelated `VehicleServiceHistoryReportPage.tsx` nullable `result.meta` error.
- The generated preview was rendered and visually checked as a single A5 landscape page with no clipping or overlap.

## Preview

- `output/pdf/service-invoice-a5-preview.pdf`
