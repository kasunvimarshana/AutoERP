# Vehicle Rental agreement revision completion

Date: 2026-07-03

Vehicle Rental agreement terms now use explicit create, update, and archive commands with optimistic versions and retained history. Rental rate corrections now create recorded-time revisions with explicit lineage and correction reasons instead of mutating active effective periods.

No compatibility aliases, destructive replace-all fallback, GitHub Actions, or force push are used.
