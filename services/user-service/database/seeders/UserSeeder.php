<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'keycloak_id' => (string) Str::uuid(),
                'email'       => 'admin@inventory.example',
                'first_name'  => 'System',
                'last_name'   => 'Administrator',
                'username'    => 'sysadmin',
                'roles'       => ['admin', 'manager'],
                'is_active'   => true,
                'department'  => 'Engineering',
            ],
            [
                'keycloak_id' => (string) Str::uuid(),
                'email'       => 'manager@inventory.example',
                'first_name'  => 'Inventory',
                'last_name'   => 'Manager',
                'username'    => 'inv.manager',
                'roles'       => ['manager'],
                'is_active'   => true,
                'department'  => 'Operations',
            ],
            [
                'keycloak_id' => (string) Str::uuid(),
                'email'       => 'viewer@inventory.example',
                'first_name'  => 'Read',
                'last_name'   => 'Only',
                'username'    => 'readonly.user',
                'roles'       => ['viewer'],
                'is_active'   => true,
                'department'  => 'Finance',
            ],
            [
                'keycloak_id' => (string) Str::uuid(),
                'email'       => 'warehouse@inventory.example',
                'first_name'  => 'Warehouse',
                'last_name'   => 'Staff',
                'username'    => 'warehouse.staff',
                'roles'       => ['manager', 'viewer'],
                'is_active'   => true,
                'department'  => 'Operations',
            ],
            [
                'keycloak_id' => null,
                'email'       => 'inactive@inventory.example',
                'first_name'  => 'Inactive',
                'last_name'   => 'User',
                'username'    => 'inactive.user',
                'roles'       => [],
                'is_active'   => false,
                'department'  => null,
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }

        $this->command->info('Users seeded successfully.');
    }
}
