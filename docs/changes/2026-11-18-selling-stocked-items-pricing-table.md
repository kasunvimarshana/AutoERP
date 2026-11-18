# Present stocked items as a pricing table on Create Selling

**Date:** 2026-11-18
**Module:** Selling

Replaced the selected-item cards on Create Selling with a pricing table matching the supplied reference: item and code, quantity, unit, resolved unit price, discount, line total, actions, and an items subtotal footer. The table scrolls horizontally when space is limited. The Edit action uses the Service Job pencil style, focuses the quantity field, and opens the item's optional dimensions in a full-width detail row. The Remove action uses the matching bin style and removes only that draft item. The stocked-item search placeholder now describes name and code search.

The Selling form still uses its existing pricing, discount calculations, draft state, and posting payload. Unit price remains read-only because it comes from configured item pricing. No API, database, or historical sale data changed.

Verification: focused Selling page tests passed (3 tests), including Edit focus, dimensions expansion, line discount calculation, and sale submission. ESLint, TypeScript typecheck, and production Vite build passed.
