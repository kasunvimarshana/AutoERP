<?php

declare(strict_types=1);

namespace Modules\Invoice\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Invoice Permissions Seeder
 *
 * Seeds permissions for Invoice module
 */
class InvoicePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Invoice permissions
            'invoice.view',
            'invoice.create',
            'invoice.edit',
            'invoice.delete',
            'invoice.generate',
            'invoice.search',
            'invoice.view_overdue',
            'invoice.view_outstanding',

            // Payment permissions
            'payment.view',
            'payment.create',
            'payment.record',
            'payment.void',
            'payment.view_history',

            // Commission permissions
            'commission.view',
            'commission.create',
            'commission.calculate',
            'commission.mark_paid',
            'commission.view_pending',
            'commission.view_by_driver',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdmin->givePermissionTo($permissions);

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo($permissions);

        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->givePermissionTo([
            'invoice.view',
            'invoice.create',
            'invoice.edit',
            'invoice.generate',
            'invoice.search',
            'invoice.view_overdue',
            'invoice.view_outstanding',
            'payment.view',
            'payment.create',
            'payment.record',
            'payment.view_history',
            'commission.view',
            'commission.create',
            'commission.calculate',
            'commission.mark_paid',
            'commission.view_pending',
            'commission.view_by_driver',
        ]);

        $accountant = Role::firstOrCreate(['name' => 'accountant']);
        $accountant->givePermissionTo([
            'invoice.view',
            'invoice.create',
            'invoice.edit',
            'invoice.generate',
            'invoice.search',
            'invoice.view_overdue',
            'invoice.view_outstanding',
            'payment.view',
            'payment.create',
            'payment.record',
            'payment.void',
            'payment.view_history',
            'commission.view',
            'commission.calculate',
            'commission.view_pending',
            'commission.view_by_driver',
        ]);

        $this->command->info('Invoice permissions seeded successfully!');
    }
}
