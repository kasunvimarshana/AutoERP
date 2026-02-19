# CVMS Module - Required Permissions

## Overview

This document lists all permissions required for the Customer & Vehicle Management System (CVMS) module. These permissions should be created in the system and assigned to appropriate roles for proper access control.

## Permission Naming Convention

Permissions follow the pattern: `{resource}.{action}`

## Customer Permissions

| Permission | Description | Required For |
|-----------|-------------|--------------|
| `customer.list` | View list of customers | Viewing customer index |
| `customer.read` | View customer details | Viewing single customer |
| `customer.create` | Create new customers | Creating customers |
| `customer.update` | Update customer information | Editing customers |
| `customer.delete` | Delete customers | Deleting customers (soft delete) |
| `customer.search` | Search customers | Using customer search |
| `customer.statistics` | View customer statistics | Viewing customer analytics |

## Vehicle Permissions

| Permission | Description | Required For |
|-----------|-------------|--------------|
| `vehicle.list` | View list of vehicles | Viewing vehicle index |
| `vehicle.read` | View vehicle details | Viewing single vehicle |
| `vehicle.create` | Create new vehicles | Creating vehicles |
| `vehicle.update` | Update vehicle information | Editing vehicles |
| `vehicle.delete` | Delete vehicles | Deleting vehicles (soft delete) |
| `vehicle.search` | Search vehicles | Using vehicle search |
| `vehicle.transfer` | Transfer vehicle ownership | Transferring vehicles between customers |
| `vehicle.update-mileage` | Update vehicle mileage | Updating odometer readings |
| `vehicle.statistics` | View vehicle statistics | Viewing vehicle analytics |

## Service Record Permissions

| Permission | Description | Required For |
|-----------|-------------|--------------|
| `service-record.list` | View list of service records | Viewing service record index |
| `service-record.read` | View service record details | Viewing single service record |
| `service-record.create` | Create new service records | Creating service records |
| `service-record.update` | Update service record information | Editing service records |
| `service-record.delete` | Delete service records | Deleting service records (soft delete) |
| `service-record.complete` | Complete a service record | Marking services as completed |
| `service-record.cancel` | Cancel a service record | Cancelling scheduled services |
| `service-record.search` | Search service records | Using service record search |
| `service-record.statistics` | View service statistics | Viewing service analytics |
| `service-record.cross-branch` | View cross-branch history | Viewing services across branches |

## Role-Based Permission Sets

### Super Admin
- All permissions (bypass all checks)

### Manager
- All customer permissions
- All vehicle permissions
- All service record permissions

### Service Advisor
- `customer.list`, `customer.read`, `customer.create`, `customer.update`, `customer.search`
- `vehicle.list`, `vehicle.read`, `vehicle.create`, `vehicle.update`, `vehicle.search`, `vehicle.update-mileage`
- `service-record.list`, `service-record.read`, `service-record.create`, `service-record.update`, `service-record.complete`, `service-record.search`, `service-record.cross-branch`

### Technician
- `customer.list`, `customer.read`
- `vehicle.list`, `vehicle.read`, `vehicle.update-mileage`
- `service-record.list`, `service-record.read`, `service-record.update`, `service-record.complete`

### Receptionist
- `customer.list`, `customer.read`, `customer.create`, `customer.search`
- `vehicle.list`, `vehicle.read`, `vehicle.search`
- `service-record.list`, `service-record.read`, `service-record.create`, `service-record.search`

### Reports Viewer (Read-Only)
- `customer.list`, `customer.read`, `customer.statistics`
- `vehicle.list`, `vehicle.read`, `vehicle.statistics`
- `service-record.list`, `service-record.read`, `service-record.statistics`, `service-record.cross-branch`

## Creating Permissions

### Using Laravel Tinker

```php
php artisan tinker

use Spatie\Permission\Models\Permission;

// Customer permissions
Permission::create(['name' => 'customer.list']);
Permission::create(['name' => 'customer.read']);
Permission::create(['name' => 'customer.create']);
Permission::create(['name' => 'customer.update']);
Permission::create(['name' => 'customer.delete']);
Permission::create(['name' => 'customer.search']);
Permission::create(['name' => 'customer.statistics']);

// Vehicle permissions
Permission::create(['name' => 'vehicle.list']);
Permission::create(['name' => 'vehicle.read']);
Permission::create(['name' => 'vehicle.create']);
Permission::create(['name' => 'vehicle.update']);
Permission::create(['name' => 'vehicle.delete']);
Permission::create(['name' => 'vehicle.search']);
Permission::create(['name' => 'vehicle.transfer']);
Permission::create(['name' => 'vehicle.update-mileage']);
Permission::create(['name' => 'vehicle.statistics']);

// Service record permissions
Permission::create(['name' => 'service-record.list']);
Permission::create(['name' => 'service-record.read']);
Permission::create(['name' => 'service-record.create']);
Permission::create(['name' => 'service-record.update']);
Permission::create(['name' => 'service-record.delete']);
Permission::create(['name' => 'service-record.complete']);
Permission::create(['name' => 'service-record.cancel']);
Permission::create(['name' => 'service-record.search']);
Permission::create(['name' => 'service-record.statistics']);
Permission::create(['name' => 'service-record.cross-branch']);
```

### Using Seeder

Create a seeder file: `database/seeders/CVMSPermissionSeeder.php`

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CVMSPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Define all permissions
        $permissions = [
            // Customer permissions
            'customer.list',
            'customer.read',
            'customer.create',
            'customer.update',
            'customer.delete',
            'customer.search',
            'customer.statistics',
            
            // Vehicle permissions
            'vehicle.list',
            'vehicle.read',
            'vehicle.create',
            'vehicle.update',
            'vehicle.delete',
            'vehicle.search',
            'vehicle.transfer',
            'vehicle.update-mileage',
            'vehicle.statistics',
            
            // Service record permissions
            'service-record.list',
            'service-record.read',
            'service-record.create',
            'service-record.update',
            'service-record.delete',
            'service-record.complete',
            'service-record.cancel',
            'service-record.search',
            'service-record.statistics',
            'service-record.cross-branch',
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $this->createManagerRole();
        $this->createServiceAdvisorRole();
        $this->createTechnicianRole();
        $this->createReceptionistRole();
        $this->createReportsViewerRole();
    }

    protected function createManagerRole(): void
    {
        $role = Role::firstOrCreate(['name' => 'manager']);
        
        // Manager gets all permissions
        $role->syncPermissions(Permission::all());
    }

    protected function createServiceAdvisorRole(): void
    {
        $role = Role::firstOrCreate(['name' => 'service-advisor']);
        
        $permissions = [
            'customer.list', 'customer.read', 'customer.create', 'customer.update', 'customer.search',
            'vehicle.list', 'vehicle.read', 'vehicle.create', 'vehicle.update', 'vehicle.search', 'vehicle.update-mileage',
            'service-record.list', 'service-record.read', 'service-record.create', 'service-record.update', 
            'service-record.complete', 'service-record.search', 'service-record.cross-branch',
        ];
        
        $role->syncPermissions($permissions);
    }

    protected function createTechnicianRole(): void
    {
        $role = Role::firstOrCreate(['name' => 'technician']);
        
        $permissions = [
            'customer.list', 'customer.read',
            'vehicle.list', 'vehicle.read', 'vehicle.update-mileage',
            'service-record.list', 'service-record.read', 'service-record.update', 'service-record.complete',
        ];
        
        $role->syncPermissions($permissions);
    }

    protected function createReceptionistRole(): void
    {
        $role = Role::firstOrCreate(['name' => 'receptionist']);
        
        $permissions = [
            'customer.list', 'customer.read', 'customer.create', 'customer.search',
            'vehicle.list', 'vehicle.read', 'vehicle.search',
            'service-record.list', 'service-record.read', 'service-record.create', 'service-record.search',
        ];
        
        $role->syncPermissions($permissions);
    }

    protected function createReportsViewerRole(): void
    {
        $role = Role::firstOrCreate(['name' => 'reports-viewer']);
        
        $permissions = [
            'customer.list', 'customer.read', 'customer.statistics',
            'vehicle.list', 'vehicle.read', 'vehicle.statistics',
            'service-record.list', 'service-record.read', 'service-record.statistics', 'service-record.cross-branch',
        ];
        
        $role->syncPermissions($permissions);
    }
}
```

Run the seeder:
```bash
php artisan db:seed --class=CVMSPermissionSeeder
```

## Assigning Permissions to Users

### Assign Role to User
```php
$user->assignRole('manager');
$user->assignRole('service-advisor');
```

### Assign Permission Directly
```php
$user->givePermissionTo('customer.create');
```

### Check Permissions
```php
if ($user->can('customer.create')) {
    // User can create customers
}

if ($user->hasRole('manager')) {
    // User is a manager
}
```

## Policy Implementation

Policies are automatically enforced for all CVMS resources:
- `CustomerPolicy` - For customer operations
- `VehiclePolicy` - For vehicle operations
- `VehicleServiceRecordPolicy` - For service record operations

Policies check both role-based permissions and attribute-based rules (e.g., can't edit old service records).

## Multi-Tenancy Considerations

All permissions are automatically scoped to the user's tenant. Users can only access resources within their own tenant, even with appropriate permissions.

## Branch-Level Permissions (Future Enhancement)

For more granular control, consider implementing branch-level permissions:
- `customer.list.branch` - Only see customers from own branch
- `service-record.list.branch` - Only see service records from own branch
- `service-record.cross-branch` - Required to see records from other branches

## API Middleware

To enforce permissions in API routes, use the `permission` middleware:

```php
Route::middleware(['auth:sanctum', 'permission:customer.create'])
    ->post('/customers', [CustomerController::class, 'store']);
```

Or check permissions in controllers:
```php
$this->authorize('create', Customer::class);
```

## Notes

1. Super admin users bypass all permission checks
2. Policies may add additional business logic rules beyond permissions
3. Some actions may require multiple permissions
4. Soft deletes preserve data while marking as deleted
5. Cross-branch permissions are important for multi-location operations
