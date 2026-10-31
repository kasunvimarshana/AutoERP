# Payment Method Wise Summary document expansion

The daily report groups transactions by date and currency, so one summary row can contain several invoices and payments. A single document number in that row would be misleading. The screen now removes its separate Currency column and expands a selected date/currency row to show its source documents on demand. Credit sale rows link to the invoice; receipt rows link to the payment and its allocated invoice. Each receipt row shows the allocated amount and its payment-method split. Currency remains visible beside the date, in formatted amounts, and in totals so different currencies cannot be mistaken for one balance.

The Reporting service uses the existing credit-sale and allocation calculations to produce document details only for the selected date. The new read-only endpoint uses the existing report permission and requires a single date. Export rows retain their currency column because exported numeric amounts need an explicit currency identifier. No schema or financial transaction changed, and no migration is needed.

Verification: PaymentMethodWiseSummaryReportTest passed (1 test, 20 assertions); the report page test passed (1 test); TypeScript typecheck, PHP syntax, Pint, route registration, and `git diff --check` passed. Targeted ESLint did not finish in the local environment and was stopped after the other checks passed.
