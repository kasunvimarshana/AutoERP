<?php

namespace Database\Seeders;

use App\Modules\Tenant\Models\Tenant;
use App\Modules\User\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['admin', 'manager', 'staff'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'api']);
        }

        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $admin = User::firstOrCreate(
                ['email' => "admin@{$tenant->slug}.example.com"],
                [
                    'tenant_id' => $tenant->id,
                    'name'      => "{$tenant->name} Admin",
                    'password'  => Hash::make('password'),
                    'role'      => 'admin',
                ]
            );
            $admin->syncRoles(['admin']);

            $manager = User::firstOrCreate(
                ['email' => "manager@{$tenant->slug}.example.com"],
                [
                    'tenant_id' => $tenant->id,
                    'name'      => "{$tenant->name} Manager",
                    'password'  => Hash::make('password'),
                    'role'      => 'manager',
                ]
            );
            $manager->syncRoles(['manager']);

            foreach (['alice', 'bob'] as $staffName) {
                $staff = User::firstOrCreate(
                    ['email' => "{$staffName}@{$tenant->slug}.example.com"],
                    [
                        'tenant_id' => $tenant->id,
                        'name'      => ucfirst($staffName) . " ({$tenant->name})",
                        'password'  => Hash::make('password'),
                        'role'      => 'staff',
                    ]
                );
                $staff->syncRoles(['staff']);
            }
        }
    }
}
