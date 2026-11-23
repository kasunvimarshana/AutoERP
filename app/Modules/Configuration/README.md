# Configuration module

The Configuration module owns registered runtime settings. It does not own business reference catalogs, tenant identity, or organization-unit identity.

## Source of truth

Runtime overrides are stored in one table per explicit scope:

- `global_configuration_values`
- `tenant_configuration_values`
- `organization_unit_configuration_values`

Resolution order is organization unit, tenant, global, then the owner-defined default. Consumers depend on `ConfigurationResolverInterface`; they must not query configuration tables directly.

Resolution uses request-scoped memoization only. Cross-request cache state is intentionally avoided so a committed configuration change cannot be hidden by stale cache data.

## Definition ownership

Each module registers only the settings it owns under `configuration.definitions`. A definition controls its human-readable label, description, value type, allowed scopes, default value, options or reference lookup, sensitivity, and runtime mutability. Unknown keys and invalid owner definitions fail explicitly.

## Security and integrity

- Tenant and organization scope come from trusted request context, never client-supplied IDs.
- Scope-specific permissions protect mutations.
- Sensitive values are encrypted and never returned by API resources.
- `row_version` is enforced with compare-and-swap updates and deletes.
- Repository mutations include the full tenant and organization scope predicates.
- Organization rows use a composite foreign key to prevent cross-tenant ownership.
- Configuration changes are recorded through the Audit module.

Reference catalogs are owned by `Modules/ReferenceData`.

## Dynamic Timezone Resolution

The Configuration module provides the authoritative runtime implementation of `Modules\Core\Contracts\TimezoneResolverInterface` via `Modules\Configuration\Services\TimezoneResolver`:

- Resolves `localization.timezone` from the active tenant or organization-unit scope via `ConfigurationResolverInterface`.
- Falls back gracefully to `config('app.timezone')` and `'UTC'` if not explicitly set on the tenant.
- Exposes dynamic date and time resolution methods:
  - `resolve(?int $tenantId, ?int $organizationUnitId): string`
  - `now(?int $tenantId, ?int $organizationUnitId): CarbonImmutable`
  - `today(?int $tenantId, ?int $organizationUnitId): CarbonImmutable`
  - `todayDateString(?int $tenantId, ?int $organizationUnitId): string`
  - `toCivilDate(DateTimeInterface|string $instant, ?int $tenantId, ?int $organizationUnitId): string`
  - `toCivilDateTime(DateTimeInterface|string $instant, ?int $tenantId, ?int $organizationUnitId): CarbonImmutable`
- Registered in `ConfigurationServiceProvider` to cleanly override the Core module's `DefaultTimezoneResolver` during application boot while keeping the module dependency graph 100% acyclic.

---

## Author & Maintainer

- **Lead Architect & Developer:** Kasun Vimarshana
- **Email:** [kasunvmail@gmail.com](mailto:kasunvmail@gmail.com)
- **Phone:** [+94713525253](tel:+94713525253)
