<?php

declare(strict_types=1);

namespace Modules\JobCard\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * JobCard Permissions Seeder
 *
 * Seeds permissions for JobCard module
 */
class JobCardPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'job_card.view',
            'job_card.create',
            'job_card.edit',
            'job_card.delete',
            'job_card.start',
            'job_card.pause',
            'job_card.resume',
            'job_card.complete',
            'job_card.assign_technician',
            'job_card.add_task',
            'job_card.remove_task',
            'job_card.add_inspection',
            'job_card.add_part',
            'job_card.remove_part',
            'job_card.view_statistics',
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
            'job_card.view',
            'job_card.create',
            'job_card.edit',
            'job_card.start',
            'job_card.pause',
            'job_card.resume',
            'job_card.complete',
            'job_card.assign_technician',
            'job_card.add_task',
            'job_card.add_inspection',
            'job_card.add_part',
            'job_card.view_statistics',
        ]);

        $technician = Role::firstOrCreate(['name' => 'technician']);
        $technician->givePermissionTo([
            'job_card.view',
            'job_card.start',
            'job_card.pause',
            'job_card.resume',
            'job_card.complete',
            'job_card.add_task',
            'job_card.add_inspection',
            'job_card.add_part',
        ]);

        $this->command->info('JobCard permissions seeded successfully!');
    }
}
