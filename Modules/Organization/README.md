# Organization Module

## Overview

The Organization module provides comprehensive multi-level organizational structure management for the AutoERP system. It supports hierarchical organizations, branches, locations, and organizational units with full tenant isolation.

## Features

- **Hierarchical Organizations**: Unlimited nesting of parent-child organization relationships
- **Multi-Location Support**: Support for branches, offices, warehouses, and other location types
- **Organizational Units**: Flexible organizational structure for departments and divisions
- **Tenant Isolation**: Full multi-tenant support with automatic scoping
- **Address Management**: Comprehensive address handling with geocoding support
- **Settings & Configuration**: Organization-level settings and configuration management
- **Status Management**: Lifecycle management (active, inactive, suspended)
- **Full CRUD API**: Complete REST API for all entities
- **Test Coverage**: 20+ comprehensive unit and feature tests

## Entities

### Organization
- Represents a company, subsidiary, or organizational entity
- Supports parent-child hierarchies
- Configurable settings and features
- Contact information and addresses
- Status: active, inactive, suspended

### Location
- Represents physical or virtual locations (offices, branches, warehouses)
- Belongs to an organization
- Supports location hierarchy
- Geocoding support (latitude/longitude)
- Operating hours and contact information
- Status: active, inactive, under_construction, closed

### OrganizationalUnit
- Represents departments, divisions, or teams
- Links to organizations and locations
- Manager assignment
- Hierarchical structure
- Status: active, inactive, suspended

## Architecture

### Database Schema

```
organizations
├── id (PK)
├── tenant_id (FK)
├── parent_id (FK, self-reference)
├── code (unique per tenant)
├── name (translatable JSON)
├── legal_name
├── tax_id
├── status (enum)
├── settings (JSON)
├── level, path (hierarchy tracking)
└── timestamps

locations
├── id (PK)
├── tenant_id (FK)
├── organization_id (FK)
├── parent_location_id (FK, self-reference)
├── code (unique per tenant)
├── name (translatable JSON)
├── location_type (enum)
├── address fields
├── latitude, longitude
├── operating_hours (JSON)
├── level, path (hierarchy tracking)
└── timestamps

organizational_units
├── id (PK)
├── tenant_id (FK)
├── organization_id (FK)
├── location_id (FK)
├── parent_unit_id (FK, self-reference)
├── code (unique per tenant)
├── name (translatable JSON)
├── unit_type (enum)
├── manager_id (FK to users)
├── level, path (hierarchy tracking)
└── timestamps
```

## Usage Examples

### Creating Organizations

```php
use Modules\Organization\Services\OrganizationService;

$organizationService = app(OrganizationService::class);

// Create parent organization
$parentOrg = $organizationService->createOrganization([
    'code' => 'HQ',
    'name' => ['en' => 'Headquarters', 'es' => 'Sede Central'],
    'legal_name' => 'Company Inc.',
    'tax_id' => 'TAX123456',
    'status' => 'active',
]);

// Create subsidiary
$subsidiary = $organizationService->createOrganization([
    'parent_id' => $parentOrg->id,
    'code' => 'SUB-01',
    'name' => ['en' => 'Regional Subsidiary'],
    'legal_name' => 'Subsidiary LLC',
    'status' => 'active',
]);
```

### Creating Locations

```php
use Modules\Organization\Services\LocationService;

$locationService = app(LocationService::class);

$location = $locationService->createLocation([
    'organization_id' => $parentOrg->id,
    'code' => 'NYC-01',
    'name' => ['en' => 'New York Office'],
    'location_type' => 'office',
    'address_line1' => '123 Main St',
    'city' => 'New York',
    'state' => 'NY',
    'postal_code' => '10001',
    'country' => 'US',
]);
```

### Creating Organizational Units

```php
use Modules\Organization\Services\OrganizationalUnitService;

$unitService = app(OrganizationalUnitService::class);

$unit = $unitService->createUnit([
    'organization_id' => $parentOrg->id,
    'code' => 'ENG-DEPT',
    'name' => ['en' => 'Engineering Department'],
    'unit_type' => 'department',
    'status' => 'active',
    'manager_id' => $userId,
]);
```

### Querying Hierarchies

```php
// Get all child organizations
$children = $organization->children;

// Get all descendants (recursive)
$descendants = $organization->descendants();

// Get ancestors (up the tree)
$ancestors = $organization->ancestors();

// Get root organization
$root = $organization->root();

// Check if organization is descendant of another
$isChild = $organization->isDescendantOf($parentOrg);

// Build tree structure
$tree = Organization::buildTree(Organization::all());
```

## Integration with Other Modules

The Organization module is designed to integrate with all other modules:

- **HR Module**: Departments and employees belong to organizations/locations
- **Sales Module**: Customers and orders can be associated with organizations
- **Inventory Module**: Warehouses are linked to locations
- **Accounting Module**: Financial records are scoped to organizations
- **Procurement Module**: Suppliers and purchases are organization-specific

## API Endpoints

### Organizations
- `GET /api/v1/organizations` - List all organizations
- `POST /api/v1/organizations` - Create organization
- `GET /api/v1/organizations/{id}` - Get organization details
- `PUT /api/v1/organizations/{id}` - Update organization
- `DELETE /api/v1/organizations/{id}` - Delete organization
- `GET /api/v1/organizations/{id}/children` - Get child organizations
- `GET /api/v1/organizations/{id}/hierarchy` - Get full hierarchy
- `GET /api/v1/organizations/{id}/descendants` - Get all descendants

### Locations
- `GET /api/v1/locations` - List all locations
- `POST /api/v1/locations` - Create location
- `GET /api/v1/locations/{id}` - Get location details
- `PUT /api/v1/locations/{id}` - Update location
- `DELETE /api/v1/locations/{id}` - Delete location
- `GET /api/v1/locations/{id}/children` - Get child locations
- `GET /api/v1/organizations/{orgId}/locations` - Get by organization

### Organizational Units (NEW)
- `GET /api/v1/organizational-units` - List all units
- `POST /api/v1/organizational-units` - Create unit
- `GET /api/v1/organizational-units/{id}` - Get unit details
- `PUT /api/v1/organizational-units/{id}` - Update unit
- `DELETE /api/v1/organizational-units/{id}` - Delete unit
- `GET /api/v1/organizational-units/{id}/children` - Get child units
- `GET /api/v1/organizational-units/{id}/hierarchy` - Get full tree
- `GET /api/v1/organizational-units/{id}/descendants` - Get all descendants
- `GET /api/v1/organizations/{orgId}/units` - Get organization's unit tree

## Testing

### Run Module Tests
```bash
# Run all Organization module tests
php artisan test --testsuite=Organization

# Run unit tests only
php artisan test Modules/Organization/Tests/Unit

# Run feature tests only
php artisan test Modules/Organization/Tests/Feature

# Run with coverage
php artisan test --testsuite=Organization --coverage
```

### Test Coverage
- **Unit Tests**: 9 test cases covering service layer business logic
- **Feature Tests**: 11 test cases covering API endpoints
- **Total**: 20+ comprehensive test cases

### Factories
Test data can be generated using factories:

```php
use Modules\Organization\Entities\Organization;
use Modules\Organization\Entities\Location;
use Modules\Organization\Entities\OrganizationalUnit;

// Create test organizations
$org = Organization::factory()->headquarters()->active()->create();
$subsidiary = Organization::factory()->subsidiary()->create(['parent_id' => $org->id]);

// Create test locations
$location = Location::factory()->warehouse()->create(['organization_id' => $org->id]);

// Create test units
$unit = OrganizationalUnit::factory()->department()->active()->create([
    'organization_id' => $org->id,
]);
```

## Dependencies

- Core Module
- Tenancy Module

## License

Proprietary - Part of AutoERP system
