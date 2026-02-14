<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            // Create Super Admin for each tenant
            $admin = User::firstOrCreate(
                [
                    'email' => "admin@{$tenant->subdomain}.com",
                    'tenant_id' => $tenant->id,
                ],
                [
                    'name' => "{$tenant->name} Admin",
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
            $this->assignRoleToUser($admin, 'Super Admin', $tenant->id);

            // Create Manager
            $manager = User::firstOrCreate(
                [
                    'email' => "manager@{$tenant->subdomain}.com",
                    'tenant_id' => $tenant->id,
                ],
                [
                    'name' => ucfirst($tenant->subdomain).' Manager',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
            $this->assignRoleToUser($manager, 'Manager', $tenant->id);

            // Create Sales user
            $sales = User::firstOrCreate(
                [
                    'email' => "sales@{$tenant->subdomain}.com",
                    'tenant_id' => $tenant->id,
                ],
                [
                    'name' => ucfirst($tenant->subdomain).' Sales Rep',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
            $this->assignRoleToUser($sales, 'Sales', $tenant->id);

            // Create Inventory Manager
            $inventoryManager = User::firstOrCreate(
                [
                    'email' => "inventory@{$tenant->subdomain}.com",
                    'tenant_id' => $tenant->id,
                ],
                [
                    'name' => ucfirst($tenant->subdomain).' Inventory Manager',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
            $this->assignRoleToUser($inventoryManager, 'Inventory Manager', $tenant->id);

            // Create Cashier
            $cashier = User::firstOrCreate(
                [
                    'email' => "cashier@{$tenant->subdomain}.com",
                    'tenant_id' => $tenant->id,
                ],
                [
                    'name' => ucfirst($tenant->subdomain).' Cashier',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
            $this->assignRoleToUser($cashier, 'Cashier', $tenant->id);

            // Create Viewer
            $viewer = User::firstOrCreate(
                [
                    'email' => "viewer@{$tenant->subdomain}.com",
                    'tenant_id' => $tenant->id,
                ],
                [
                    'name' => ucfirst($tenant->subdomain).' Viewer',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
            $this->assignRoleToUser($viewer, 'Viewer', $tenant->id);
        }

        $this->command->info('Users created successfully for all tenants!');
    }

    private function assignRoleToUser(User $user, string $roleName, int $tenantId): void
    {
        $role = Role::where('name', $roleName)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($role) {
            // Check if the role is already assigned
            $exists = DB::table('model_has_roles')
                ->where('role_id', $role->id)
                ->where('model_type', get_class($user))
                ->where('model_id', $user->id)
                ->where('tenant_id', $tenantId)
                ->exists();

            if (! $exists) {
                DB::table('model_has_roles')->insert([
                    'role_id' => $role->id,
                    'model_type' => get_class($user),
                    'model_id' => $user->id,
                    'tenant_id' => $tenantId,
                ]);
            }
        }
    }
}
