# Revert the promotion timezone fix

At the user's request, removed the timezone changes recorded in `2026-11-12-promotion-business-date-timezone.md`. Laravel and workspace timezone defaults are UTC again; Promotion date checks use their previous `now()->toDateString()` behavior. Removed the Promotion business-date helper, its boundary test, and the local `.env` timezone override.

Other in-progress Promotion and Vehicle Service changes were preserved. The original change record remains in the append-only history so the attempted fix and its reversal are both visible.
