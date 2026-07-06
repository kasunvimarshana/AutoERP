# Invoice Print Audit Findings

## Context

Reviewed the current invoice print/PDF flow after recent tenant-scope hardening. The goal was to identify wrong or incomplete behavior without changing application code.

## Findings

1. Printed totals are recalculated in the Blade template instead of using the persisted invoice totals.
   - `resources/views/invoice/print.blade.php` derives line amount from `unit_price * quantity`, then derives VAT as a hardcoded 18% and grand total as `base * 1.18`.
   - The invoice engine already stores `subtotal`, `discount_total`, `tax_total`, `charge_total`, `adjustment_total`, and `grand_total`.
   - This can print a different total from the approved invoice when discounts, inclusive tax, withholding, freight, charges, source adjustments, non-18% taxes, or zero-rated tax apply.

2. The print template reads live customer/supplier relationships and non-existent fields instead of invoice snapshots.
   - Invoice creation captures immutable party snapshots such as `party_name_snapshot`, `party_tax_registration_snapshot`, and `party_phone_snapshot`.
   - The print view uses `$invoice->party`, `$invoice->customer`, `tin`, `address`, `party_name`, and `customer_name`, several of which are not invoice fields.
   - Printed party details can be blank, stale, or changed by later master-data edits instead of preserving the original invoice values.

3. Direction and party roles are not modeled in the print output.
   - The template always renders fixed Supplier/Purchaser boxes.
   - Inbound purchase invoices and outbound customer invoices need different labels and source of organization/party details.
   - The current model only snapshots the counterparty, while organization-unit or tenant supplier details are not clearly supplied to the print document.

4. Print/PDF routing is duplicated and partially outside the invoice module boundary.
   - PDF rendering closures are repeated in `routes/web.php` for singular, plural, and public routes.
   - `InvoiceController::printView()` exists, but PDF generation is not owned by the invoice controller or an invoice print service.
   - This makes it easy for print and PDF behavior to drift.

5. The signed print-link endpoint does not validate invoice scope before issuing a public URL.
   - `signedPrintLink()` signs URLs from the requested invoice id and current tenant id without first resolving the invoice through the same tenant and organization-unit scope used by `show()`.
   - The public route will fail closed if the id is not in the tenant, but the API still issues plausible links for invoices outside the current organization-unit scope or for missing invoices.

6. The print document contains sample/static legal text and unsupported fields.
   - It prints "GOVERNMENT OF SRI LANKA - SAMPLE", "EOG 11 - 0124", `delivery_date`, `place_of_supply`, `amount_in_words`, and `payment_mode`.
   - These are not backed by the current invoice schema, so the document looks official but may be incomplete or misleading.

7. There is no dedicated print contract test.
   - Existing invoice tests cover calculation, snapshots, lifecycle, and source allocation, but no test asserts that print/PDF output uses stored invoice totals and immutable snapshots.

## Recommended Fix Direction

- Add an invoice-owned print view model/service that prepares display-safe values from persisted invoice snapshots and totals.
- Keep PDF and HTML print rendering behind one invoice-owned controller path.
- Use stored `line_total`, `subtotal`, `discount_total`, `tax_total`, `charge_total`, `adjustment_total`, and `grand_total`; never recompute financial totals in Blade.
- Use immutable party snapshots for counterparty details and define a clear source for organization/tenant supplier details before printing tax invoices.
- Validate invoice existence through the scoped invoice lookup before issuing signed print links.
- Add print/PDF tests covering inclusive tax, discounts/withholding, changed party master data, missing invoice, and wrong organization-unit scope.

## Verification

- Static code inspection only; no application code was changed.
