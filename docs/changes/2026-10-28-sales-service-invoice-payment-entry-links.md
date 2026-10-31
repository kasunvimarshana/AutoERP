# Sales and service invoice payment entry links

Added `Receive payment` to outstanding, posted or partially paid sales invoice details when the user has access to the existing Selling payment entry. It opens the same `/payments/create?invoice_id=...&view=selling` flow already used by Selling sale details. Supplier payment actions remain unchanged.

The service invoice detail action now passes its invoice ID to the linked job payment page. That page preselects the requested invoice only when it is among the job's currently payable invoices. Without a valid requested invoice, the existing one-invoice default and manual selection remain available. Payment creation and backend validation were not changed.

Extended invoice action and vehicle service payment tests. Updated stale vehicle service tests to the current direct-finalize UI so the existing payment flow is covered. Verification: four focused Vitest files passed (21 tests), TypeScript typecheck passed, targeted ESLint reported no errors and one pre-existing effect warning, and `git diff --check` passed.
