# Migration key naming convention

All explicitly named database keys and constraints must use this format:

```text
<table-or-clear-alias>_<business-purpose>_<suffix>
```

Supported suffixes:

| Key type | Suffix |
|---|---|
| Foreign key | `_fk` |
| Unique key | `_uk` |
| Normal index | `_ix` |
| Custom or composite primary key | `_pk` |
| Check constraint | `_ck` |

## Rules

1. Names must describe the table and relationship or query purpose.
2. Names must be no longer than 60 characters. This leaves a safety margin below MySQL's 64-character identifier limit.
3. Do not use mixed suffixes such as `_foreign`, `_unique`, `_index`, or `_idx`.
4. Every `foreignId()->constrained()` declaration must provide an explicit `indexName`.
5. Every explicit `foreign()`, `unique()`, `index()`, and custom `primary()` declaration must have an explicit name.
6. `$table->id()` remains the standard single-column auto-increment primary key. MySQL exposes that key as `PRIMARY`; a custom `_pk` name is only used for explicit non-standard or composite primary declarations.
7. Composite referenced indexes must be declared before the foreign keys that depend on them, especially self-referencing foreign keys.
8. Correct original create migrations. Do not add key-renaming compatibility migrations while the project is still using fresh development schemas.

## Examples

```php
$table->foreignId('tenant_id')->constrained(
    'tenants',
    indexName: 'users_tenant_fk',
);

$table->unique(
    ['tenant_id', 'email'],
    'users_tenant_email_uk',
);

$table->index(
    ['tenant_id', 'status'],
    'users_tenant_status_ix',
);

$table->primary(
    ['tenant_id', 'code'],
    'reference_values_tenant_code_pk',
);
```
