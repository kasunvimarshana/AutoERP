# Service Cards & Offers reimplementation plan

Reviewed the current checkout and the recent change records before planning. The Promotion module is absent; Vehicle Service already owns job lines and transactional invoice posting, Invoice owns restoration, and Audit offers an event recorder. The supplied SQL backup has no promotion text.

Added `docs/architecture/service-cards-and-offers-reimplementation-plan-2026-11-03.md` with the confirmed behavior, module boundaries, schema, count and redemption rules, UI, permissions, implementation order, and acceptance gates. This is planning only: no application code, database schema, or customer data was changed.
