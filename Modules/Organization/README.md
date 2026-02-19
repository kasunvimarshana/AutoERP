# Organization & Branch Management Module

A production-ready Laravel module for managing organizations and branches in a multi-tenant vehicle service center SaaS application.

## Overview

This module provides comprehensive organization and branch management functionality, supporting:

- **Single, Multi-Branch, and Franchise organizations**
- **GPS-based branch location tracking**
- **Cross-branch operations support**
- **Branch capacity management**
- **Multi-tenancy with data isolation**
- **Role-Based Access Control (RBAC)**
- **Multi-language support (EN, ES, FR)**

## Architecture

The module follows the **Controller → Service → Repository** pattern with strict separation of concerns:

```
HTTP Request → Controller → Service → Repository → Model → Database
```

## Features

### Organization Management

- Create, read, update, delete operations
- Auto-generation of unique organization numbers
- Support for different organization types:
  - Single branch
  - Multi-branch
  - Franchise
- Tax ID and registration tracking
- Complete address and contact management
- Status management (active, inactive, suspended)
- Search functionality

### Branch Management

- Full CRUD operations for branches
- Auto-generation of unique branch codes
- GPS coordinates for location tracking
- Operating hours configuration
- Service offerings management
- Vehicle capacity tracking
- Bay count management
- Branch status management
- Find nearby branches using GPS
- Cross-branch data access
- Capacity checking

## Installation

1. The module is auto-discovered by Laravel Modules package
2. Run migrations:
   ```bash
   php artisan migrate
   ```
3. Seed permissions:
   ```bash
   php artisan db:seed --class=Modules\\Organization\\Database\\Seeders\\OrganizationPermissionsSeeder
   ```

## API Endpoints

### Organizations

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/organizations` | List all organizations |
| POST | `/api/v1/organizations` | Create new organization |
| GET | `/api/v1/organizations/{id}` | Get organization details |
| PUT | `/api/v1/organizations/{id}` | Update organization |
| DELETE | `/api/v1/organizations/{id}` | Delete organization |
| GET | `/api/v1/organizations/search?query=...` | Search organizations |

### Branches

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/branches` | List all branches |
| POST | `/api/v1/branches` | Create new branch |
| GET | `/api/v1/branches/{id}` | Get branch details |
| PUT | `/api/v1/branches/{id}` | Update branch |
| DELETE | `/api/v1/branches/{id}` | Delete branch |
| GET | `/api/v1/branches/organization/{id}` | Get branches by organization |
| GET | `/api/v1/branches/search?query=...` | Search branches |
| GET | `/api/v1/branches/nearby` | Find nearby branches |
| GET | `/api/v1/branches/{id}/capacity` | Check branch capacity |

## Usage Examples

### Create Organization

```bash
POST /api/v1/organizations
Content-Type: application/json
Authorization: Bearer {token}

{
  "name": "ABC Auto Service",
  "legal_name": "ABC Auto Service Ltd.",
  "type": "multi_branch",
  "email": "contact@abcauto.com",
  "phone": "+1234567890",
  "address": "123 Main St",
  "city": "New York",
  "state": "NY",
  "postal_code": "10001",
  "country": "US"
}
```

### Create Branch

```bash
POST /api/v1/branches
Content-Type: application/json
Authorization: Bearer {token}

{
  "organization_id": 1,
  "name": "Downtown Branch",
  "email": "downtown@abcauto.com",
  "phone": "+1234567890",
  "address": "456 Park Ave",
  "city": "New York",
  "state": "NY",
  "latitude": 40.7589,
  "longitude": -73.9851,
  "capacity_vehicles": 25,
  "bay_count": 8,
  "operating_hours": {
    "monday": {"open": "08:00", "close": "18:00"},
    "tuesday": {"open": "08:00", "close": "18:00"},
    "wednesday": {"open": "08:00", "close": "18:00"},
    "thursday": {"open": "08:00", "close": "18:00"},
    "friday": {"open": "08:00", "close": "18:00"},
    "saturday": {"open": "09:00", "close": "14:00"},
    "sunday": null
  },
  "services_offered": [
    "oil_change",
    "tire_rotation",
    "brake_service",
    "engine_diagnostic"
  ]
}
```

### Find Nearby Branches

```bash
GET /api/v1/branches/nearby?latitude=40.7589&longitude=-73.9851&radius=10
Authorization: Bearer {token}
```

### Check Branch Capacity

```bash
GET /api/v1/branches/1/capacity?current_vehicles=15
Authorization: Bearer {token}
```

## Permissions

The module defines the following permissions:

### Organization Permissions
- `organization.list` - List organizations
- `organization.read` - View organization details
- `organization.create` - Create organizations
- `organization.update` - Update organizations
- `organization.delete` - Delete organizations

### Branch Permissions
- `branch.list` - List branches
- `branch.read` - View branch details
- `branch.create` - Create branches
- `branch.update` - Update branches
- `branch.delete` - Delete branches

## Testing

Run tests for this module:

```bash
# Run all organization module tests
php artisan test --filter=Organization

# Run specific test class
php artisan test Modules/Organization/tests/Feature/OrganizationApiTest.php
php artisan test Modules/Organization/tests/Feature/BranchApiTest.php
```

## Database Schema

### Organizations Table

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| organization_number | string | Unique organization identifier |
| name | string | Organization name |
| legal_name | string | Legal business name |
| type | enum | single, multi_branch, franchise |
| status | enum | active, inactive, suspended |
| tax_id | string | Tax identification number |
| registration_number | string | Business registration number |
| email | string | Organization email |
| phone | string | Organization phone |
| website | string | Organization website |
| address | text | Street address |
| city | string | City |
| state | string | State/Province |
| postal_code | string | Postal/ZIP code |
| country | string | Country code (ISO 3166-1 alpha-2) |
| metadata | json | Additional metadata |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Last update timestamp |
| deleted_at | timestamp | Soft delete timestamp |

### Branches Table

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| organization_id | bigint | Parent organization (FK) |
| branch_code | string | Unique branch code |
| name | string | Branch name |
| status | enum | active, inactive, maintenance |
| manager_name | string | Branch manager name |
| email | string | Branch email |
| phone | string | Branch phone |
| address | text | Street address |
| city | string | City |
| state | string | State/Province |
| postal_code | string | Postal/ZIP code |
| country | string | Country code |
| latitude | decimal | GPS latitude |
| longitude | decimal | GPS longitude |
| operating_hours | json | Operating hours |
| services_offered | json | Available services |
| capacity_vehicles | integer | Max vehicle capacity per day |
| bay_count | integer | Number of service bays |
| metadata | json | Additional metadata |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Last update timestamp |
| deleted_at | timestamp | Soft delete timestamp |

## Localization

The module supports multiple languages:

- English (en)
- Spanish (es)
- French (fr)

Translation files are located in `lang/` directory.

## Code Quality

- ✅ **PSR-12 Compliant**: 100%
- ✅ **Type Safety**: Full type hints on all methods
- ✅ **Documentation**: PHPDoc on all classes and methods
- ✅ **SOLID Principles**: Applied throughout
- ✅ **DRY & KISS**: No code duplication
- ✅ **Test Coverage**: Feature tests for all endpoints

## Security

- Multi-tenant data isolation
- RBAC authorization on all endpoints
- Input validation using FormRequest classes
- Transactional integrity for multi-step operations
- Audit trails via AuditTrait
- Soft deletes for data safety

## Contributing

When contributing to this module:

1. Follow the existing code style
2. Write comprehensive tests
3. Update documentation
4. Run `./vendor/bin/pint` before committing
5. Ensure all tests pass

## License

This module is part of the ModularSaaS Laravel-Vue application.
