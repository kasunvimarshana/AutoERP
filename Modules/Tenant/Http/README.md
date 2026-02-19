# Tenant Module REST API

Complete REST API implementation for Tenant and Organization management with full CRUD operations, hierarchical support, and enterprise-grade security.

## 📁 Directory Structure

```
modules/Tenant/Http/
├── Controllers/
│   ├── TenantController.php           # Tenant CRUD operations
│   └── OrganizationController.php     # Organization CRUD + hierarchical ops
├── Requests/
│   ├── StoreTenantRequest.php         # Tenant creation validation
│   ├── UpdateTenantRequest.php        # Tenant update validation
│   ├── StoreOrganizationRequest.php   # Organization creation with hierarchy validation
│   ├── UpdateOrganizationRequest.php  # Organization update validation
│   └── MoveOrganizationRequest.php    # Organization move validation
└── Resources/
    ├── TenantResource.php             # Tenant API response formatting
    └── OrganizationResource.php       # Organization API response formatting (with relationships)
```

## 🚀 API Endpoints

### Tenant Management

All tenant operations require JWT authentication. Create and Delete operations are restricted to users with appropriate permissions.

#### List Tenants
```http
GET /api/v1/tenants
```

**Query Parameters:**
- `active` (boolean): Filter by active status
- `search` (string): Search by name, slug, or domain
- `sort_by` (string): Sort field (default: created_at)
- `sort_order` (string): asc|desc (default: desc)
- `per_page` (integer): Results per page (default: 15)

**Response:**
```json
{
  "success": true,
  "data": [...],
  "meta": {...},
  "links": {...}
}
```

#### Create Tenant
```http
POST /api/v1/tenants
```

**Request Body:**
```json
{
  "name": "Acme Corporation",
  "slug": "acme-corp",
  "domain": "acme.example.com",
  "settings": {
    "timezone": "UTC",
    "currency": "USD"
  },
  "is_active": true
}
```

**Validation:**
- `name`: required, string, max:255
- `slug`: required, unique, alpha_dash, max:100
- `domain`: required, unique, max:255
- `settings`: optional, array
- `is_active`: optional, boolean

#### Get Tenant
```http
GET /api/v1/tenants/{id}
```

#### Update Tenant
```http
PUT /api/v1/tenants/{id}
```

**Request Body:**
```json
{
  "name": "Updated Name",
  "domain": "updated.example.com",
  "settings": {...},
  "is_active": true
}
```

#### Delete Tenant (Soft Delete)
```http
DELETE /api/v1/tenants/{id}
```

#### Restore Tenant
```http
POST /api/v1/tenants/{id}/restore
```

---

### Organization Management

All organization operations require JWT authentication and tenant context.

#### List Organizations
```http
GET /api/v1/organizations
```

**Query Parameters:**
- `active` (boolean): Filter by active status
- `type` (string): Filter by organization type (company|division|department|team)
- `parent_id` (string|null): Filter by parent (use "null" for root organizations)
- `search` (string): Search by name or code
- `sort_by` (string): Sort field (default: name)
- `sort_order` (string): asc|desc (default: asc)
- `per_page` (integer): Results per page (default: 15)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "tenant_id": "uuid",
      "parent_id": null,
      "name": "Acme Corp",
      "code": "ACME",
      "type": "company",
      "type_label": "Company",
      "level": 1,
      "metadata": {},
      "is_active": true,
      "parent": null,
      "children": [...],
      "created_at": "2024-01-01T00:00:00+00:00",
      "updated_at": "2024-01-01T00:00:00+00:00"
    }
  ],
  "meta": {...},
  "links": {...}
}
```

#### Create Organization
```http
POST /api/v1/organizations
```

**Request Body:**
```json
{
  "name": "Engineering Department",
  "code": "ENG",
  "type": "department",
  "parent_id": "parent-uuid",
  "metadata": {
    "location": "Building A",
    "cost_center": "CC-001"
  },
  "is_active": true
}
```

**Validation:**
- `name`: required, string, max:255
- `code`: required, unique within tenant, alpha_dash, max:50
- `type`: required, one of: company|division|department|team
- `parent_id`: optional, exists in organizations table
- `metadata`: optional, array
- `is_active`: optional, boolean

**Hierarchy Validation:**
- Maximum depth check (default: 10 levels)
- No circular references
- Parent must belong to same tenant

#### Get Organization
```http
GET /api/v1/organizations/{id}
```

#### Update Organization
```http
PUT /api/v1/organizations/{id}
```

**Request Body:**
```json
{
  "name": "Updated Name",
  "code": "NEW-CODE",
  "type": "division",
  "metadata": {...},
  "is_active": true
}
```

**Note:** Cannot change parent_id via update. Use move endpoint instead.

#### Delete Organization (Soft Delete)
```http
DELETE /api/v1/organizations/{id}
```

**Note:** Cannot delete organizations with children.

#### Restore Organization
```http
POST /api/v1/organizations/{id}/restore
```

---

### Hierarchical Operations

#### Get Child Organizations
```http
GET /api/v1/organizations/{id}/children
```

Returns immediate children of the organization.

**Query Parameters:**
- `active` (boolean): Filter by active status

**Response:**
```json
{
  "success": true,
  "data": [...]
}
```

#### Get Ancestors (Parent Hierarchy)
```http
GET /api/v1/organizations/{id}/ancestors
```

Returns all parent organizations up to the root.

**Response:**
```json
{
  "success": true,
  "data": [
    {"id": "parent-uuid", "name": "Parent", "level": 1},
    {"id": "grandparent-uuid", "name": "Grandparent", "level": 0}
  ]
}
```

#### Get Descendants (Full Tree Below)
```http
GET /api/v1/organizations/{id}/descendants
```

Returns all descendant organizations recursively.

**Response:**
```json
{
  "success": true,
  "data": [...]
}
```

#### Move Organization to Different Parent
```http
PUT /api/v1/organizations/{id}/move
```

**Request Body:**
```json
{
  "parent_id": "new-parent-uuid"
}
```

**Validation:**
- Cannot move to self
- Cannot move to descendant (circular reference check)
- Maximum depth check after move
- Updates all descendant levels recursively

**Response:**
```json
{
  "success": true,
  "message": "Organization moved successfully",
  "data": {...}
}
```

---

## 🔒 Security Features

### Authentication & Authorization
- **JWT Authentication**: All endpoints require valid JWT token
- **Policy-Based Authorization**: Laravel policies enforce permissions
- **Tenant Isolation**: Organizations are strictly scoped to tenant context
- **Permission Checks**: Fine-grained permission validation

### Required Permissions

**Tenant Operations:**
- `tenants.view` - View tenants
- `tenants.create` - Create new tenants
- `tenants.update` - Update tenant information
- `tenants.delete` - Delete tenants
- `tenants.restore` - Restore deleted tenants

**Organization Operations:**
- `organizations.view` - View organizations
- `organizations.create` - Create new organizations
- `organizations.update` - Update organization information
- `organizations.delete` - Delete organizations
- `organizations.restore` - Restore deleted organizations

### Data Validation
- **Unique Constraints**: Slug/domain uniqueness for tenants, code uniqueness per tenant for organizations
- **Hierarchy Validation**: Depth limits, circular reference prevention
- **Type Validation**: Organization types validated against config
- **Custom Validation**: Additional validation in request classes

---

## 🛡️ Data Integrity

### Transaction Safety
All create, update, delete, and move operations are wrapped in database transactions.

### Concurrency Support
- Optimistic locking support via model versioning
- Safe for concurrent access
- Proper foreign key constraints

### Audit Logging
All operations are automatically logged via AuditService:
- `tenant.created`, `tenant.updated`, `tenant.deleted`, `tenant.restored`
- `organization.created`, `organization.updated`, `organization.deleted`, `organization.restored`, `organization.moved`

### Soft Deletes
- Both tenants and organizations support soft deletion
- Cascade delete prevention (organizations with children)
- Restore functionality with parent validation

---

## 📊 Response Format

### Success Response
```json
{
  "success": true,
  "message": "Operation successful",
  "data": {...}
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error message",
  "errors": {...}
}
```

### Validation Error
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

### Paginated Response
```json
{
  "success": true,
  "data": [...],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7,
    "from": 1,
    "to": 15
  },
  "links": {
    "first": "http://...",
    "last": "http://...",
    "prev": null,
    "next": "http://..."
  }
}
```

---

## 🔧 Configuration

Organization types and hierarchy limits are configurable in `config/tenant.php`:

```php
'organizations' => [
    'max_depth' => 10,
    'types' => [
        'company' => 'Company',
        'division' => 'Division',
        'department' => 'Department',
        'team' => 'Team',
    ],
],
```

---

## 🧪 Testing Examples

### Create a Tenant
```bash
curl -X POST http://localhost/api/v1/tenants \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Acme Corporation",
    "slug": "acme-corp",
    "domain": "acme.example.com",
    "is_active": true
  }'
```

### Create Root Organization
```bash
curl -X POST http://localhost/api/v1/organizations \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant-ID: tenant-uuid" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Acme Corporation",
    "code": "ACME",
    "type": "company",
    "parent_id": null,
    "is_active": true
  }'
```

### Create Child Organization
```bash
curl -X POST http://localhost/api/v1/organizations \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant-ID: tenant-uuid" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Engineering Division",
    "code": "ENG",
    "type": "division",
    "parent_id": "parent-org-uuid",
    "is_active": true
  }'
```

### Move Organization
```bash
curl -X PUT http://localhost/api/v1/organizations/{id}/move \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant-ID: tenant-uuid" \
  -H "Content-Type: application/json" \
  -d '{
    "parent_id": "new-parent-uuid"
  }'
```

### Get Organization Tree
```bash
# Get all descendants
curl -X GET http://localhost/api/v1/organizations/{id}/descendants \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant-ID: tenant-uuid"

# Get ancestors
curl -X GET http://localhost/api/v1/organizations/{id}/ancestors \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant-ID: tenant-uuid"
```

---

## 📝 Implementation Notes

### Base Classes
- **Controllers**: Extend `App\Http\Controllers\ApiController`
- **Requests**: Extend `App\Http\Requests\ApiRequest`
- **Resources**: Extend `Illuminate\Http\Resources\Json\JsonResource`

### Dependencies
- `TenantContext`: Manages current tenant and organization context
- `AuditService`: Logs all critical operations
- Native Laravel validation (no third-party packages)

### Hierarchical Operations
- Level is automatically calculated and maintained
- Moving organizations updates all descendant levels
- Circular reference prevention at validation layer
- Maximum depth configurable via config

### PSR-12 Compliance
- Strict types enabled
- Comprehensive docblocks
- Production-ready code
- No placeholders

---

## ✅ Feature Checklist

- [x] TenantController with full CRUD
- [x] OrganizationController with full CRUD
- [x] Hierarchical operations (children, ancestors, descendants)
- [x] Organization move with validation
- [x] Soft delete support
- [x] Restore functionality
- [x] Form request validation
- [x] API resources for response formatting
- [x] Authorization policies
- [x] Audit logging integration
- [x] Transaction safety
- [x] Tenant context integration
- [x] Unique constraint validation
- [x] Hierarchy depth validation
- [x] Circular reference prevention
- [x] Organization type validation
- [x] Pagination support
- [x] Search and filtering
- [x] Route registration
- [x] Policy registration
- [x] PSR-12 compliance
- [x] Strict types
- [x] Production-ready
