# Simplify the Selling create form

**Date:** 2026-11-17
**Module:** Selling

The stocked-item table on Create Selling compressed quantity, price, and discount controls, wrapped long discount labels, and pushed the line total and remove action out of view. The separate Workflow guidance panel also reduced the form's usable width.

Removed that panel and gave the form the available page width. Each selected item now has a responsive card with a clear item name, quantity controls, unit price, short discount label, line total, and remove action. Optional inventory dimensions remain with the item. Money values in the form use the shared formatter for readable grouping and decimal precision. The full item name remains in the discount controls' accessible labels.

This changes presentation only. Sale submission, discount calculations, API contracts, and persistence remain in their existing modules. No schema or relationship changes were needed.

Verification: focused Selling page tests passed (3 tests); ESLint and `git diff --check` passed. TypeScript typecheck and production Vite build were run for the final check.
