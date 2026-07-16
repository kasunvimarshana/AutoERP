# Private object storage behavior coverage

Date: 2026-07-16

## Problem

Private Object storage had limited direct behavioral evidence despite owning security-sensitive path normalization and streamed private-file operations.

## Correction

Added focused tests for:

- store, exists, size, list, streamed read, delete, and post-delete state;
- explicit alternate-disk isolation;
- unreadable source rejection;
- unsafe filename rejection;
- traversal-segment rejection;
- empty directory-path rejection;
- empty disk-name rejection.

The tests use Laravel fake disks and temporary local source files. Production storage behavior, adapters, configuration, and schema were not changed.

## Relationships

No model or database relationship changed. Private Object remains a cross-cutting storage abstraction; business modules remain responsible for object ownership and authorization before calling it.

## Verification

Run:

```bash
git diff --check
php artisan test --filter=PrivateObjectStorageServiceBehaviorTest
php artisan test
composer test:mysql
```
