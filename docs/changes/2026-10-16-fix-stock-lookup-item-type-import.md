# Fix stock lookup ItemType resolution

## Request

Fix the 500 response when searching stock items from the Purchase Order selector.

## Root cause

`ItemQueryService::lookup` referenced `ItemType::Stock` without importing the enum. PHP resolved the name in the `Modules\\Item\\Services` namespace and failed with `Class "Modules\\Item\\Services\\ItemType" not found`.

## Changes

- Imported `Modules\\Item\\Enums\\ItemType` in `ItemQueryService`.

## Verification

- PHP syntax check passed for `ItemQueryService.php`.
- Item lookup route is registered and `git diff --check` passed.
