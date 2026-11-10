# Selling payment exchange rate initialization

**Date:** 2026-11-10

The Selling invoice payment screen cleared its exchange rate after loading the invoice. The Finalize payment button still required a positive rate whenever the invoice had a currency, while this screen did not expose a rate field. Entering the full cash amount therefore left the button disabled.

Payment entry now initializes the rate from the invoice's stored `exchange_rate`. The Selling screen provides an Exchange rate section so the operator can review or change it; the section opens automatically when the invoice rate is missing or differs from the base rate. The request still sends the selected rate to backend payment validation.

Added Selling settlement tests for a base-rate invoice and an edited foreign-currency rate. Both verify that entering the cash amount enables finalization and sends the rate with the payment. The existing generic invoice settlement tests remain covered.

Verification: Payment invoice settlement Vitest suite passed (4 tests); TypeScript typecheck, focused ESLint, and `git diff --check` passed.
