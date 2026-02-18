# IAM Module - Identity and Access Management

## Overview

The IAM (Identity and Access Management) module provides comprehensive user, role, permission, and group management capabilities for the AutoERP system. This module is built using native Laravel features without any third-party packages.

## Features

### ✅ Permission Management
- Create, read, update, and delete permissions
- Organize permissions by module, resource, and action
- Support for permission metadata and descriptions
- Active/inactive permission status
- Bulk permission creation
- Auto-generate CRUD permissions for resources

### ✅ Role-Permission Assignment
- Assign multiple permissions to roles
- Many-to-many relationship between roles and permissions
- Sync permissions to roles

### ✅ User-Permission Management
- Direct permission assignment to users
- Grant or deny permissions at user level
- Override role permissions with user-specific permissions

### ✅ Group Management
- Create user groups/teams
- Hierarchical group structure (parent-child)
- Assign roles to groups
- Manage group members
- Multi-tenant support

### ✅ Native Implementation
- Uses Laravel's native Eloquent ORM
- Native migration system
- Native validation with Form Requests
- Native event system for audit logging
- Native API Resource transformers

## Installation

### 1. Run Migrations

```bash
php artisan migrate
```

This will create the following tables:
- `permissions` - Store all permissions
- `permission_role` - Role-permission pivot table
- `user_permissions` - User-permission pivot table with type (grant/deny)
- `groups` - User groups/teams
- `group_user` - Group-user pivot table
- `group_role` - Group-role pivot table

### 2. Seed Default Permissions

```bash
php artisan db:seed --class=Modules\\IAM\\Database\\Seeders\\IAMDatabaseSeeder
```

This will create default IAM permissions for:
- Permission management (view, create, update, delete, assign)
- Role management (view, create, update, delete, assign)
- Group management (view, create, update, delete, manage-members)
- User management (view, create, update, delete, manage-permissions)

## API Endpoints

All endpoints require authentication using Laravel Sanctum.

### Permission Endpoints

```
GET    /api/v1/iam/permissions              - List all permissions
POST   /api/v1/iam/permissions              - Create new permission
GET    /api/v1/iam/permissions/active       - Get active permissions only
GET    /api/v1/iam/permissions/search       - Search permissions
GET    /api/v1/iam/permissions/module/{module} - Get permissions by module
POST   /api/v1/iam/permissions/generate-crud - Generate CRUD permissions
GET    /api/v1/iam/permissions/{id}         - Get permission details
PUT    /api/v1/iam/permissions/{id}         - Update permission
DELETE /api/v1/iam/permissions/{id}         - Delete permission
POST   /api/v1/iam/permissions/assign/role/{roleId} - Assign to role
POST   /api/v1/iam/permissions/assign/user/{userId} - Assign to user
```

### Permission API Examples

#### Create Permission

```bash
curl -X POST http://localhost/api/v1/iam/permissions \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "View Customers",
    "slug": "sales.customer.view",
    "module": "sales",
    "resource": "customer",
    "action": "view",
    "description": "Permission to view customer data",
    "is_active": true
  }'
```

#### Generate CRUD Permissions

```bash
curl -X POST http://localhost/api/v1/iam/permissions/generate-crud \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "module": "sales",
    "resource": "customer"
  }'
```

This will automatically create:
- sales.customer.view
- sales.customer.create
- sales.customer.update
- sales.customer.delete

#### Assign Permissions to Role

```bash
curl -X POST http://localhost/api/v1/iam/permissions/assign/role/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "permission_ids": [1, 2, 3, 4]
  }'
```

#### Assign Permissions to User

```bash
curl -X POST http://localhost/api/v1/iam/permissions/assign/user/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "permission_ids": [1, 2, 3],
    "type": "grant"
  }'
```

## Usage in Code

### Check Permission

```php
// In controller
if ($request->user()->hasPermission('sales.customer.view')) {
    // User has permission
}

// Using Gate
if (Gate::allows('sales.customer.view')) {
    // User has permission
}

// Using authorization
$this->authorize('sales.customer.view');
```

### Get User Permissions

```php
$user = auth()->user();
$permissions = $user->getPermissions(); // Returns array of permission slugs
```

### Check Multiple Permissions

```php
// Check if user has any of the permissions
$user->hasAnyPermission(['sales.customer.view', 'sales.customer.create']);

// Check if user has all permissions
$user->hasAllPermissions(['sales.customer.view', 'sales.customer.update']);
```

### Working with Groups

```php
use Modules\IAM\Entities\Group;

// Create a group
$group = Group::create([
    'name' => 'Sales Team',
    'slug' => 'sales-team',
    'description' => 'Sales department members',
]);

// Add users to group
$group->addUser($user);

// Assign role to group
$group->assignRole($role);

// Check group permissions
if ($group->hasPermission('sales.customer.view')) {
    // Group has permission
}
```

## Service Layer

### PermissionService

```php
use Modules\IAM\Services\PermissionService;

$permissionService = app(PermissionService::class);

// Create permission
$permission = $permissionService->createPermission([
    'name' => 'View Orders',
    'slug' => 'sales.order.view',
    'module' => 'sales',
    'resource' => 'order',
    'action' => 'view',
]);

// Update permission
$permission = $permissionService->updatePermission($id, [
    'description' => 'Updated description',
]);

// Delete permission
$permissionService->deletePermission($id);

// Assign permissions to role
$permissionService->assignPermissionsToRole($roleId, [1, 2, 3]);

// Assign permissions to user
$permissionService->assignPermissionsToUser($userId, [1, 2], 'grant');

// Generate CRUD permissions
$permissions = $permissionService->generateCrudPermissions('sales', 'customer');
```

## Events

The IAM module dispatches the following events:

- `PermissionCreated` - When a permission is created
- `PermissionUpdated` - When a permission is updated
- `PermissionDeleted` - When a permission is deleted
- `PermissionAssignedToRole` - When permissions are assigned to a role
- `PermissionAssignedToUser` - When permissions are assigned to a user

## Database Schema

### permissions

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | string | Display name |
| slug | string | Unique identifier |
| module | string | Module name |
| resource | string | Resource name |
| action | string | Action name |
| description | text | Description |
| metadata | json | Additional data |
| is_active | boolean | Active status |
| created_at | timestamp | Creation time |
| updated_at | timestamp | Update time |

### groups

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| tenant_id | bigint | Tenant ID (multi-tenancy) |
| name | string | Group name |
| slug | string | Unique identifier |
| description | text | Description |
| parent_id | bigint | Parent group ID |
| is_active | boolean | Active status |
| created_at | timestamp | Creation time |
| updated_at | timestamp | Update time |
| deleted_at | timestamp | Soft delete |

## Testing

Run IAM module tests:

```bash
# Run all IAM tests
php artisan test --testsuite=IAM

# Run unit tests only
php artisan test Modules/IAM/Tests/Unit

# Run feature tests only
php artisan test Modules/IAM/Tests/Feature

# Run with coverage
php artisan test --testsuite=IAM --coverage
```

## Configuration

Module configuration is located at `Modules/IAM/Config/config.php`.

### Default Permissions

Configure default permissions that should be created during installation.

### Permission Categories

Define module categories for organizing permissions.

## Security Considerations

1. **Authorization**: Always check permissions before performing actions
2. **Multi-tenancy**: Groups are tenant-scoped automatically
3. **Audit Trail**: All permission changes are logged using the `LogsActivity` trait
4. **Direct Permissions**: User-specific permissions can override role permissions
5. **Permission Types**: Support both "grant" and "deny" permission types

## Best Practices

1. **Permission Naming**: Use the format `module.resource.action`
2. **Descriptive Names**: Use clear, descriptive permission names
3. **Granular Permissions**: Create specific permissions rather than broad ones
4. **Role-Based**: Assign permissions to roles, not individual users (unless needed)
5. **Groups**: Use groups to manage permissions for teams/departments
6. **Active Status**: Deactivate unused permissions rather than deleting them

## Future Enhancements

- [ ] Permission caching for improved performance
- [ ] Permission inheritance in hierarchical groups
- [ ] Time-based permission grants (temporary access)
- [ ] Permission request and approval workflow
- [ ] Advanced permission conditions (attribute-based access control)
- [ ] Permission usage analytics

## Support

For issues, questions, or contributions, please refer to the main project documentation.

## License

This module is part of the AutoERP system and follows the same license.
