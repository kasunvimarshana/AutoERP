# Consumable Usage local Finance setup

Date: 2026-10-30

## Problem

Posting Consumable Usage in the local AUTOERP tenant returned `Posting profile [internal_consumption] is missing or inactive for this scope.` The existing default organization unit had no `internal_consumption` profile or Operating Expense account. The prior implementation had added these definitions to `FinanceSeeder`, but the local database had not been synchronized.

## Action

- Inspected the local tenant, organization unit, accounts, roles, and profiles. Existing Finance records were default seeder records.
- Ran the existing `FinanceSeeder` in the project's required control-plane execution context. A direct `db:seed` attempt stopped without changes because it lacked that context.
- Verified active `internal_consumption` profile and rules for Inventory and Operating Expense, active account-role assignments, and active posting accounts `1200` and `5400` in the local organization unit.

No application code, migrations, or historical transactions were changed. The user can retry posting the usage entry.
