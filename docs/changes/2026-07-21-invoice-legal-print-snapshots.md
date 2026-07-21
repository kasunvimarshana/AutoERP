# Invoice legal print snapshots

## Context

Invoice print/PDF already used persisted financial totals, but several legal-document fields were blank or sourced asymmetrically. Organization units had no owned legal/tax profile, invoices snapshotted only the external counterparty header, and the print template hardcoded every document as a Tax Invoice.

## Foundation

- Organization Unit owns an optional versioned legal/tax profile containing legal name, TIN, VAT/SVAT registration, registered address, telephone, and email.
- Invoice captures one immutable document snapshot for new documents:
  - seller legal identity
  - purchaser legal identity
  - supply date and period
  - place of supply
  - agreed payment mode and terms
  - resolved document kind
- A missing organization legal profile never blocks invoice creation.
- When the profile is absent, the organization unit name is snapshotted as the seller/buyer name and unavailable registration, address, and contact values remain `null`.
- Optional fields print blank without warnings, watermarks, fabricated values, or live-master-data fallback.
- Existing historical invoices are not backfilled from current master data.

## Source ownership

- Organization Unit owns the optional company/branch legal identity and the editor in the existing detail drawer.
- Customer and Supplier own their legal identity, tax identifiers, contacts, and addresses.
- Invoice owns immutable legal-document snapshots and print rendering.
- Vehicle Rental supplies its calculation period and agreement payment terms to Invoice.
- Payment remains the owner of actual settlement methods; invoice print shows agreed issue-time payment mode/terms only.

## Document kind resolution

- Outbound document with output tax and a snapshotted seller VAT registration number: `Tax Invoice`
- Other outbound document: `Invoice`
- Other inbound document: `Purchase Invoice`
- Inbound Vehicle Rental settlement: `Owner Payable Voucher`
- Credit/debit documents: `Credit Note` / `Debit Note`

Tax lines alone do not label an unregistered organization document as a Tax Invoice.

## Vehicle Rental mapping

- VAT-registered customer-side taxable rental document: `Tax Invoice`
- Other customer-side rental document: `Invoice`
- Owner-side rental settlement: `Owner Payable Voucher`
- Supply date: calculation period end
- Supply period: calculation period start through end
- Place of supply: explicit override, otherwise the configured organization unit registered address snapshot
- Payment terms: agreement `payment_terms_days`

## Print output

The shared HTML/PDF view renders prepared immutable values for:

- Date of Invoice
- dynamic document number label and number
- available Supplier TIN, VAT/SVAT registration, legal name, address, and telephone
- available Purchaser TIN, VAT/SVAT registration, legal name, address, and telephone
- Date of Delivery / Supply and supply period
- Place of Supply
- Total Amount in words generated from persisted grand total
- agreed Mode of Payment and payment terms

Financial totals continue to use persisted invoice values and are never recalculated in the template.

## Deployment behavior

The organization legal/tax profile is optional. Configure only the details applicable to the organization. TIN, VAT, SVAT, address, telephone, and email are never fabricated when unavailable.

## Verification matrix

```bash
php artisan test --filter=OrganizationUnitLegalProfileServiceTest
php artisan test --filter=InvoiceAmountInWordsFormatterTest
php artisan test --filter=InvoiceLegalDocumentSnapshotTest
php artisan test --filter=InvoicePrintServiceTest
php artisan test --filter=Invoice
php artisan test --filter=VehicleRental
php artisan test
composer test:mysql

npm run typecheck -- --pretty false
npm run lint
npx vitest run resources/js/modules/invoice/invoiceLegalPrint.test.ts
npm run test
npm run build
```
