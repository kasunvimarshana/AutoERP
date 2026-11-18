# Revert the continuous invoice readability update

**Date:** 2026-11-18
**Module:** Invoice

At the user's request, restored the 8.5 x 5.5 inch outbound invoice print code to its state before commit `458557685`. This removes the 12px main text, 12.5px Vehicle No treatment, centered quantity column, uppercase supplier name in the party box, reduced row padding, and higher signature position. The previous change record remains in the append-only history. A4, invoice data, and database schema were not changed.

Verification: The four restored code files have no diff from commit `0001640d0`, whose InvoicePrintServiceTest suite had passed (14 tests, 149 assertions). `git diff --check` passed.
