# New customer deployment and fresh seed review

**Date:** 2026-11-18  
**Branch:** `worktree-0.0.8`  
**Scope:** Read-only review of deployment, migrations, and seeders

Reviewed the proposed separate cPanel subdomain, application copy, and new database setup. The application supports shared-schema tenancy with exact hostname mappings; a new customer does not inherently require a separate application installation or database. A separate installation can still be chosen for operational isolation, provided its web root, environment, build, secrets, storage, workers, and database are isolated. In production, the platform host and tenant workspace host must be distinct: the domain normalizer rejects a platform host as a tenant domain, and the resolver does not map central hosts to tenants.

`DatabaseSeeder` calls 17 seeders. They create the default tenant and root organization, reference data, sequences, units, warehouse, finance accounts and posting profiles, payment methods, item categories and sample items, and sample customer, supplier, vehicle, and HR data. The sample customer, supplier, vehicle, employees, and items are development/demo records and must be reviewed before giving a customer a live workspace. The platform operator seeder is disabled unless explicitly configured. The tenant seeder creates a Draft tenant; seeding alone does not create a usable customer login, active plan/subscription, verified primary domain, or active tenant. Complete those through the platform onboarding workflow.

Module service providers register their migrations, including Selling upgrade migrations that add bill and line discount columns. `migrate:fresh --seed` would rebuild every table on its configured connection, so it is appropriate only for a verified empty, dedicated database. It must never target the existing customer or platform database. A copied deployment must not carry forward its old `.env` or cached configuration. Frontend Vite URLs are embedded at build time and require a build using the new deployment values.

Verification: `ModuleMigrationBaselineTest` passed (1 test, 3 assertions); `FinanceSeederTest` passed (3 tests, 38 assertions). Source review confirms registration and seeder contents. A live cPanel or fresh MySQL migration/seed was not performed. An isolated `migrate:fresh --seed` verification attempt was rejected by the command policy before execution. No application code, schema, or existing database was changed.
