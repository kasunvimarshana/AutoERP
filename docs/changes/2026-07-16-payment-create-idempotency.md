# Payment create idempotency and transaction ownership

Date: 2026-07-16

## Problem

The generic Payment create endpoint created a new financial document for every accepted request. A browser retry, network retry, duplicate submission, or a response lost after commit could therefore create duplicate receipts or owner/supplier payments.

Payment reference validation also ran before the creation transaction. That separated mutable-reference validation from the authoritative payment, line, allocation, lifecycle, and idempotency writes.

## Correction

Payment now owns an explicit create-command idempotency contract:

- clients send an opaque `Idempotency-Key` request header;
- the Payment module uses a named operation and named result keys;
- the existing Idempotency module acquires and completes the record inside the same database transaction as Payment creation;
- an exact retry returns the existing Payment;
- reusing the same key with a different normalized commercial payload is rejected;
- validation failures roll back the idempotency record with the rest of the transaction;
- Payment validation now runs inside the authoritative creation transaction;
- the Payment method row used for the line snapshot is locked before the line is written.

The frontend Payment API retains one key for an uncertain failed request and reuses it only for an exact payload retry. A successful response releases the key, so a later intentional payment with identical values remains a new command.

## Ownership and relationships

No database schema or model relationship changed.

Payment remains the owner of:

- payment command identity;
- payment payload normalization;
- payment creation and replay behavior;
- line and allocation creation;
- payment lifecycle events.

Idempotency remains the owner of scoped key acquisition, payload-conflict detection, completion state, and persisted command results. No duplicate idempotency table or compatibility path was introduced.

Existing Customer, Supplier, Currency, Payment Method, Invoice allocation, and Finance relationships were reviewed and retained because they represent valid business responsibilities. This change does not merge, duplicate, or add bidirectional relationships.

## Verification

Focused coverage verifies:

- exact retries return the same Payment and do not duplicate lines;
- one key cannot be reused for a different payload;
- failed validation rolls back the idempotency record and permits a corrected retry;
- the frontend reuses a key after an uncertain failure;
- a successful response releases the retained key.

Run:

```bash
git diff --check
php artisan test --filter=PaymentCreationIdempotencyTest
composer test:mysql -- --filter=PaymentCreationIdempotencyTest
npx vitest run resources/js/modules/payment/paymentApi.test.ts --reporter=dot --silent
php artisan test
composer test:mysql
npm run typecheck -- --pretty false
npm run lint
npm run test
npm run build
```
