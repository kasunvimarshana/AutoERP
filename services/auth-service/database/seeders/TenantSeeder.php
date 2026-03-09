<?php

namespace Database\Seeders;

use App\Domain\Models\Tenant;
use App\Domain\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        // Create the default system tenant
        $systemTenant = Tenant::firstOrCreate(
            ['subdomain' => 'system'],
            [
                'id'       => Str::uuid()->toString(),
                'name'     => 'System Tenant',
                'subdomain'=> 'system',
                'plan'     => 'enterprise',
                'status'   => 'active',
                'settings' => [
                    'timezone' => 'UTC',
                    'locale'   => 'en',
                ],
                'features' => [
                    'multi_factor_auth' => true,
                    'device_tracking'   => true,
                    'audit_log'         => true,
                    'rbac'              => true,
                    'abac'              => true,
                    'sso'               => true,
                    'api_access'        => true,
                ],
                'config'   => [],
            ]
        );

        $this->command->info("System tenant created/found: {$systemTenant->id}");

        // Create the super-admin user
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@saas.local'],
            [
                'id'        => Str::uuid()->toString(),
                'tenant_id' => $systemTenant->id,
                'name'      => 'Super Administrator',
                'email'     => 'superadmin@saas.local',
                'password'  => Hash::make('SuperAdmin@2024!'),
                'timezone'  => 'UTC',
                'locale'    => 'en',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Assign super-admin role
        if (!$superAdmin->hasRole('super-admin')) {
            $superAdmin->assignRole('super-admin');
        }

        $this->command->info("Super admin created/found: {$superAdmin->email}");

        // Create a demo tenant
        $demoTenant = Tenant::firstOrCreate(
            ['subdomain' => 'demo'],
            [
                'id'       => Str::uuid()->toString(),
                'name'     => 'Demo Company',
                'subdomain'=> 'demo',
                'plan'     => 'pro',
                'status'   => 'active',
                'settings' => [
                    'timezone' => 'America/New_York',
                    'locale'   => 'en',
                ],
                'features' => [
                    'multi_factor_auth' => false,
                    'device_tracking'   => true,
                    'audit_log'         => true,
                    'rbac'              => true,
                    'abac'              => false,
                    'sso'               => false,
                    'api_access'        => true,
                ],
                'config'   => [],
            ]
        );

        $this->command->info("Demo tenant created/found: {$demoTenant->id}");

        // Create demo tenant admin
        $demoAdmin = User::firstOrCreate(
            ['email' => 'admin@demo.saas.local'],
            [
                'id'        => Str::uuid()->toString(),
                'tenant_id' => $demoTenant->id,
                'name'      => 'Demo Administrator',
                'email'     => 'admin@demo.saas.local',
                'password'  => Hash::make('DemoAdmin@2024!'),
                'timezone'  => 'America/New_York',
                'locale'    => 'en',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        if (!$demoAdmin->hasRole('tenant-admin')) {
            setPermissionsTeamId($demoTenant->id);
            $demoAdmin->assignRole('tenant-admin');
        }

        $this->command->info("Demo tenant admin created/found: {$demoAdmin->email}");
    }
}
