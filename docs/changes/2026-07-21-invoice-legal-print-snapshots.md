# Invoice legal print snapshots

## Context

Invoice print/PDF already used persisted financial totals, but several legal-document fields were blank or sourced asymmetrically. Organization units had no owned legal/tax profile, invoices snapshotted only the external counterparty header, and the print template hardcoded every document as a Tax Invoice.

## Foundation

- Organization Unit now owns one versioned legal/tax profile containing legal name, TIN, VAT/SVAT registration, registered address, telephone, and email.
- Invoice captures one immutable document snapshot for new documents:
  - seller legal identity
  - purchaser legal identity
  - supply date and period
  - place of supply
  - agreed payment mode and terms
  - resolved document kind
- Organization-scoped business invoices fail closed when the selected organization unit has no legal/tax profile.
- Tenant-level/internal invoice-engine documents remain supported and print a visible missing-profile warning.
- Existing historical invoices are not backfilled from current master data. Their print output carries a visible warning instead of fabricating historical legal facts.

## Source ownership

- Organization Unit owns the company/branch legal identity and the editor in the existing detail drawer.
- Customer and Supplier own their legal identity, tax identifiers, contacts, and addresses.
- Invoice owns immutable legal-document snapshots and print rendering.
- Vehicle Rental supplies its calculation period and agreement payment terms to Invoice.
- Payment remains the owner of actual settlement methods; invoice print shows agreed issue-time payment mode/terms only.

## Vehicle Rental mapping

- Customer-side taxable rental document: `Tax Invoice`
- Customer-side non-tax rental document: `Invoice`
- Owner-side rental settlement: `Owner Payable Voucher`
- Supply date: calculation period end
- Supply period: calculation period start through end
- Place of supply: explicit override, otherwise the organization unit registered address snapshot
- Payment terms: agreement `payment_terms_days`

## Print output

The shared HTML/PDF view now renders prepared immutable values for:

- Date of Invoice
- dynamic document number label and number
- Supplier TIN, VAT/SVAT registration, legal name, address, and telephone
- Purchaser TIN, VAT/SVAT registration, legal name, address, and telephone
- Date of Delivery / Supply and supply period
- Place of Supply
- Total Amount in words generated from persisted grand total
- agreed Mode of Payment and payment terms

Financial totals continue to use persisted invoice values and are never recalculated in the template.

## Deployment requirement

Configure the legal and tax profile for each organization unit that creates business invoices before releasing invoice creation to production users. No production legal identity is guessed or backfilled by the migration.

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
