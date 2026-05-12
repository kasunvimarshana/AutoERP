# Canonical ERP Seeder Pack

This folder contains reference-data seeders for the redesigned ERP schema.

Files:
- CanonicalSchemaSeeder.php
- CanonicalDocumentTypesSeeder.php
- CanonicalPermissionsSeeder.php
- CanonicalUomsSeeder.php
- CanonicalInventoryAdjustmentReasonsSeeder.php

Execution order:
1. migrate the canonical schema pack under back/new_db_design/migrations
2. create at least one tenant row
3. run CanonicalSchemaSeeder

Notes:
- These seeders are written as standard Laravel seeders but are intentionally stored outside the current active app tree.
- They assume tenant-owned reference data, so they seed data for all existing tenant IDs.
- If you later move the canonical schema into the real Laravel app, these files can be copied into database/seeders with minimal change.
