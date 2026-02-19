<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RbacSeeder extends Seeder
{
    private array $permissions = [
        'tenants.view', 'tenants.create', 'tenants.update', 'tenants.delete',
        'organizations.view', 'organizations.create', 'organizations.update', 'organizations.delete',
        'users.view', 'users.create', 'users.update', 'users.delete',
        'roles.view', 'roles.create', 'roles.update', 'roles.delete',
        'products.view', 'products.create', 'products.update', 'products.delete',
        'inventory.view', 'inventory.create', 'inventory.update', 'inventory.delete',
        'orders.view', 'orders.create', 'orders.update', 'orders.delete', 'orders.confirm', 'orders.cancel',
        'invoices.view', 'invoices.create', 'invoices.update', 'invoices.send', 'invoices.void',
        'payments.view', 'payments.create', 'payments.refund',
        'crm.contacts.view', 'crm.contacts.create', 'crm.contacts.update', 'crm.contacts.delete',
        'crm.leads.view', 'crm.leads.create', 'crm.leads.update', 'crm.leads.convert',
        'reports.view', 'reports.export',
        'audit.view',
        'settings.view', 'settings.update',
    ];

    private array $roles = [
        'super-admin' => '*',
        'tenant-admin' => [
            'organizations.view', 'organizations.create', 'organizations.update',
            'users.view', 'users.create', 'users.update',
            'roles.view', 'roles.create', 'roles.update',
            'products.view', 'products.create', 'products.update',
            'inventory.view', 'inventory.create', 'inventory.update',
            'orders.view', 'orders.create', 'orders.update', 'orders.confirm', 'orders.cancel',
            'invoices.view', 'invoices.create', 'invoices.update', 'invoices.send',
            'payments.view', 'payments.create',
            'crm.contacts.view', 'crm.contacts.create', 'crm.contacts.update',
            'crm.leads.view', 'crm.leads.create', 'crm.leads.update', 'crm.leads.convert',
            'reports.view', 'audit.view', 'settings.view', 'settings.update',
        ],
        'manager' => [
            'organizations.view',
            'users.view',
            'products.view',
            'inventory.view', 'inventory.create', 'inventory.update',
            'orders.view', 'orders.create', 'orders.update', 'orders.confirm', 'orders.cancel',
            'invoices.view', 'invoices.create', 'invoices.update', 'invoices.send',
            'payments.view', 'payments.create',
            'crm.contacts.view', 'crm.contacts.create', 'crm.contacts.update',
            'crm.leads.view', 'crm.leads.create', 'crm.leads.update', 'crm.leads.convert',
            'reports.view',
        ],
        'staff' => [
            'products.view',
            'inventory.view',
            'orders.view', 'orders.create',
            'invoices.view',
            'payments.view',
            'crm.contacts.view', 'crm.contacts.create',
            'crm.leads.view', 'crm.leads.create',
        ],
        'viewer' => [
            'products.view', 'inventory.view', 'orders.view',
            'invoices.view', 'payments.view',
            'crm.contacts.view', 'crm.leads.view',
            'reports.view',
        ],
    ];

    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        foreach ($this->roles as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'api']);
            $role->syncPermissions($perms === '*' ? $this->permissions : $perms);
        }
    }
}
