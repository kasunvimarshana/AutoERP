# Anchor short service invoice signatures and footer near page bottom

**Date:** 2026-11-17  
**Module:** Invoice  
**Scope:** Existing 8.5 x 5.5 inch service invoice layout only.

The Customer/Cashier signature lines and the thank-you/print-trace footer on short continuous-paper service invoices appeared directly after the policy text, leaving unused space below them. Wrapped the service invoice's main content and gave that content a layout-owned minimum height for the continuous-paper variant. Short invoices now use the available page height before the signatures; longer content can grow normally so the footer does not overlap invoice details. The A4 service invoice and other invoice layouts do not receive this spacing rule.

The one-line invoice was visually rendered and checked against the user's marked screenshot. The updated preview PDF is one page at 612 x 396 points (8.5 x 5.5 inches). The Invoice print suite passed: 12 tests, 119 assertions, including one-line and four-line continuous invoices, A4, A5, and purchase print paths. Pint and `git diff --check` passed. No database values, relationships, calculations, or history were changed.
