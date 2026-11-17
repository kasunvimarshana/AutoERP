# Integrate the service invoice continuous layout into worktree-0.0.8

**Date:** 2026-11-17
**Module:** Invoice

Applied the 8.5 x 5.5 inch Service Invoice print layout, footer spacing, focused tests, and preview PDF from `sadheera/prormotion-card` to `worktree-0.0.8`. This is an invoice-only integration of source commit `6029aa373`; its unrelated cPanel diagnosis record was excluded. The target branch's later Invoice controller, request, balance, and settlement changes were retained.

Verification on the target branch: `InvoicePrintServiceTest.php` passed (12 tests, 120 assertions), Pint passed for the changed PHP files, and the preview PDF is one 612 x 396 point page. No database schema, relationships, or invoice history changed. This record does not claim a hosting deployment.
