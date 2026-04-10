# ERP Models Bundle

This bundle contains Laravel Eloquent models generated from the ERP knowledge base.

## Structure
- `app/Modules/Shared/Models/BaseModel.php`
- `app/Modules/Shared/Models/Concerns/BelongsToTenant.php`
- `app/Modules/<Module>/Models/*.php`

## Design choices
- Shared `BaseModel` keeps the model layer DRY.
- Tenant-owned models include `tenant_id` in fillable fields.
- JSON-heavy fields are cast to arrays.
- date/datetime fields are cast for safer serialization.
- Key FK relations are declared with `belongsTo` for navigation.
- Polymorphic audit/trace entities stay polymorphic by design.

## Notes
- The generated code is intentionally pragmatic and safe for modular Laravel apps.
- You can extend each model with domain methods, scopes, and query helpers without touching persistence rules.
- Relationship coverage is focused on the key domain links that were explicit in the schema.
