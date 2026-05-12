# Canonical ERP Migration Pack

This folder contains a fresh migration pack for the redesigned AutoERP database architecture.

Files:
- 2026_05_10_000001_create_foundation_schema.php
- 2026_05_10_000002_create_party_and_catalog_schema.php
- 2026_05_10_000003_create_inventory_schema.php
- 2026_05_10_000004_create_commercial_schema.php
- 2026_05_10_000005_create_finance_and_audit_schema.php

Notes:
- This pack is a clean target architecture scaffold, not a drop-in replacement for the current live schema.
- It is intentionally placed under back/new_db_design/migrations to stay separate from existing active migration tracks.
- Some editor diagnostics may appear because this folder is outside the active Laravel application migration path, but the files are standard Laravel migration classes.
- The design source of truth remains the blueprint pack under back/new_db_design/blueprint_v2.

Recommended next step:
- split these phase files into module-specific migration directories only after the canonical schema is approved.