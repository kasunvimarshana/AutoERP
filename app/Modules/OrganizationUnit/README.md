# OrganizationUnit Module

This module is derived from schema-first design using:
`app/Modules/OrganizationUnit/Infrastructure/Persistence/Eloquent/Migrations`.

It follows Core, Configuration, and Tenant architecture patterns:
- Interface-driven dependencies
- Domain normalization service
- Repository contract + Eloquent implementation
- Use-case services returning `Result`
- HTTP controllers/requests/resources
- Module provider and route/migration loading