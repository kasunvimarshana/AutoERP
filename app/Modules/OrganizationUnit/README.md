# OrganizationUnit module

The OrganizationUnit module owns organization-unit identity, hierarchy, types, documents, and trusted ownership checks. Runtime settings are not stored in this module; they are owned by `Modules/Configuration`.

## Responsibilities

- Organization-unit hierarchy and lifecycle
- Organization-unit types
- Organization-unit document metadata
- Trusted tenant ownership verification through `OrganizationUnitOwnershipCheckerInterface`

Organization-specific configuration overrides are stored in `organization_unit_configuration_values`. The configuration table uses a composite foreign key to ensure the organization unit belongs to the same tenant.
