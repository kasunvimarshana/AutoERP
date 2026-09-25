# Vehicle service history development rollback

Date: 2026-09-25

## Request

The user requested a complete rollback of the newly developed old-system vehicle-history import before restarting the work with a different approach.

## Rolled back

- Removed the imported old-system history batch and all 18 imported history jobs by rolling back exactly the six vehicle-history migrations.
- Removed the six imported-history tables and their migration records.
- Restored the Vehicle Service History report service, report UI, report types, and Vehicle Service provider to their pre-development versions.
- Removed the SQL dump reader, import service, console command, imported-history models, migrations, and focused parser test introduced by this development.

## Preserved

- Preserved the user-approved rebuilt current-system baseline and master data.
- Preserved 1,339 customers, 1,891 vehicles, 3 live Vehicle Service jobs, 3 inventory movements, and 3 finance journal entries.
- Preserved unrelated current migrations and tables, including Expense.
- Preserved all earlier change records as append-only project history.

## Verification

- Imported-history batch and job tables no longer exist.
- Expense tables still exist.
- No tracked application changes from the vehicle-history development remain.
- No old-system history import data remains in the database.
