<?php

declare(strict_types=1);

namespace Modules\Appointment\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

/**
 * Permission Seeder
 *
 * Seeds permissions for the Appointment module
 */
class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Appointment permissions
            'appointment.view',
            'appointment.create',
            'appointment.update',
            'appointment.delete',
            'appointment.confirm',
            'appointment.start',
            'appointment.complete',
            'appointment.cancel',
            'appointment.reschedule',
            'appointment.assign-bay',

            // Bay permissions
            'bay.view',
            'bay.create',
            'bay.update',
            'bay.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $this->command->info('Appointment module permissions created successfully.');
    }
}
