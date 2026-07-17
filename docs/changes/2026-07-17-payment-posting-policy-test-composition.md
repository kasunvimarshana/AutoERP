# Payment posting policy test composition fix

Date: 2026-07-17
Module owner: Payment

## Problem

`PaymentPostingPolicyService` gained the canonical `PaymentRefundPolicyService` dependency, but `PaymentPostingPolicyTest` still instantiated the service with an empty constructor. Both SQLite and MySQL full suites therefore failed before exercising the posting policy assertions.

## Root cause

The pure PHPUnit unit test owned its service composition and was not updated when the production constructor contract changed. The production dependency-injection contract was correct and did not require a compatibility constructor or optional dependency.

## Change

- Added one test-local `policy()` factory.
- Composed `PaymentPostingPolicyService` with `PaymentRefundPolicyService` explicitly.
- Reused the factory in all four posting-policy tests.
- Left production services and container bindings unchanged.

## Verification requested

```bash
php artisan test --filter=PaymentPostingPolicyTest
php artisan test
composer test:mysql
```

This is a test-owned correction only; no business rule, schema, relationship, API, or runtime service behavior changed.
