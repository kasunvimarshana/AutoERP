# Add general address during customer creation

Added an optional customer address field to the Basic new-customer form and the new-customer section of the service-job vehicle registration modal. Both flows store the value as a primary `general` record in the existing `customer_addresses` relationship through the customer with-relations create endpoint, so customer and address are saved in one transaction. The ordinary customer create endpoint remains in use when no address is entered.

Added `general` to the customer address type enum and frontend options. The new-customer form keeps its existing Addresses tab for other address types; that tab excludes a second general entry during creation to avoid duplicate primary general addresses. No schema migration was needed.

Verified with the focused customer creation and service-job modal UI tests, TypeScript typecheck, lint on changed frontend files, the customer service persistence test, PHP syntax checks, and `git diff --check`.
