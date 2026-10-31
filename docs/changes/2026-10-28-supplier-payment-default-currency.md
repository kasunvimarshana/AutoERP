# Supplier payment default currency

The supplier payment context now returns the tenant's configured base currency as a named resource. A new supplier payment preselects that currency, including after changing the supplier. An invoice or purchase order opened as the payment source still supplies its own currency, so a foreign-currency source is not overwritten when the context request completes.

The Invoice Allocations search remains scoped to the selected supplier's outstanding purchase invoices and filters invoice numbers. Allocation amounts for multiple invoices remain in form state across search and pagination; the backend enforces the same supplier, currency, scope, and invoice balance.

Verification: focused supplier payment context API test, six supplier payment form tests, TypeScript typecheck, and PHP Pint passed.
