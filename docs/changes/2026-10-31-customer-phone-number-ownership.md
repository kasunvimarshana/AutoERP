# Customer phone and WhatsApp number ownership

Date: 2026-10-31

## Request

Enforce one customer per phone/WhatsApp number within a business, including Service Job new-customer creation, ordinary customer creation/editing, and customer contacts. Names may repeat. Do not automatically merge or delete existing duplicates.

## Changes

- Added Customer-owned number validation shared by customer creation, customer updates, and contact creation/update/replacement. The Service Job quick-create and legacy importer already use CustomerCreationService, so they receive the same backend enforcement. Phone and WhatsApp fields are checked against both fields on other customers and their non-deleted contacts across all branches of the tenant. The same customer's repeated number is allowed.
- Extracted Core phone normalization from WhatsApp link generation so both use one algorithm and the existing configured country calling code. Local, country-prefixed, `+` and `00` formats compare consistently. Original saved values remain unchanged; invalid new values are rejected and invalid legacy values appear in the audit.
- Number-changing application writes run in a transaction holding the tenant row lock before acquiring customer/contact/sequence locks. Ownership checks use locking reads, including when called inside an existing transaction, to avoid stale snapshot reads during simultaneous saves. This deliberately scans existing stored formats instead of rewriting customer history or maintaining a second ownership store; number checks are linear in the tenant's customer/contact count. Direct SQL writes are outside this service contract.
- Archived customers retain ownership of their saved numbers, preventing deletion/recreation from bypassing validation. Deleted contact rows do not reserve a number independently. Inactive customers and non-deleted inactive contacts still participate.
- Added a structured duplicate response with readable customer details and field feedback. Create/Edit, contact forms, and Service Job quick-create show a persistent explanation and a permission-aware link to the existing customer. Other-branch conflicts are blocked without exposing that branch's customer identity. Archived records direct users to an administrator.
- Added contact row versions with a portable additive migration. Contact edit/delete require the version originally read and reject stale writes. The Customer relation hook now passes the selected row to deletion callbacks so Contacts can send their version; other relation behavior is unchanged.
- Added the read-only `customers:audit-phone-numbers` command with optional CSV output. It lists numbers shared by different customers and invalid stored numbers, identifies customer/contact fields using readable labels, and protects CSV cells from formula execution. It never merges, deletes, or edits customer data.

## Deployment and existing data

Run the contact-version migration together with the application update. A fresh migration is required because the contacts table is already deployed. Existing duplicates remain available for review and must be corrected deliberately; attempts to save conflicting numbers are rejected.

To produce the review list against the intended database:

```powershell
php artisan customers:audit-phone-numbers BUSINESS_CODE --output=customer-phone-review.csv
```

Replace `BUSINESS_CODE` with the tenant's business code. A production audit or production migration was not run from this development task. Blank optional numbers remain optional; identity matching is based on supplied numbers, not names.

## Verification

- Customer number-ownership regression tests: 5 passed, 48 assertions, covering normalized cross-field collisions, same names with different numbers, same-owner reuse, contact ownership, atomic related-create rollback, invalid inputs, stale contact update/delete, tenant isolation, archived ownership, and read-only audit CSV generation.
- Existing Customer engine and WhatsApp link tests: 8 passed, 34 assertions.
- Service Job quick-create UI regression passed: duplicate feedback links to the existing customer, preserves input, and prevents vehicle creation.
- TypeScript typecheck and targeted ESLint checks; existing effect-state warnings are outside this change.
- Pint and `git diff --check` passed.

Database tests use SQLite. The tenant/ownership locking strategy is implemented for transactional production databases; a multi-process MySQL/PostgreSQL contention test was not run here. No customer relationships or financial/historical transactions were changed.
