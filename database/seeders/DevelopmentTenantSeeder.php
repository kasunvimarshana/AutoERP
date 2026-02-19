<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * DevelopmentTenantSeeder
 *
 * Seeds a default tenant and organization for development/testing
 */
class DevelopmentTenantSeeder extends Seeder
{
    /**
     * Run the database seeds
     */
    public function run(): void
    {
        $tenantId = Str::uuid()->toString();
        $organizationId = Str::uuid()->toString();
        $now = now();

        // Create default tenant
        DB::table('tenants')->insert([
            'id' => $tenantId,
            'name' => 'ACME Corporation',
            'slug' => 'acme',
            'domain' => 'acme.localhost',
            'settings' => json_encode([
                'timezone' => 'UTC',
                'currency' => 'USD',
                'date_format' => 'Y-m-d',
                'time_format' => 'H:i:s',
            ]),
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->command->info('✓ Created tenant: ACME Corporation (ID: '.$tenantId.')');

        // Create root organization
        DB::table('organizations')->insert([
            'id' => $organizationId,
            'tenant_id' => $tenantId,
            'parent_id' => null,
            'name' => 'ACME Corporation',
            'code' => 'ACME-ROOT',
            'type' => 'company',
            'metadata' => json_encode([
                'address' => '123 Enterprise Ave, Tech City, TC 12345',
                'phone' => '+1-555-0100',
                'email' => 'info@acme.example.com',
            ]),
            'level' => 0,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->command->info('✓ Created root organization: ACME Corporation (ID: '.$organizationId.')');

        // Create departments
        $departments = [
            ['name' => 'Sales Department', 'code' => 'SALES', 'type' => 'department'],
            ['name' => 'Finance Department', 'code' => 'FINANCE', 'type' => 'department'],
            ['name' => 'Operations Department', 'code' => 'OPS', 'type' => 'department'],
            ['name' => 'IT Department', 'code' => 'IT', 'type' => 'department'],
        ];

        foreach ($departments as $dept) {
            $deptId = Str::uuid()->toString();
            DB::table('organizations')->insert([
                'id' => $deptId,
                'tenant_id' => $tenantId,
                'parent_id' => $organizationId,
                'name' => $dept['name'],
                'code' => $dept['code'],
                'type' => $dept['type'],
                'metadata' => json_encode([]),
                'level' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $this->command->info('  ✓ Created '.$dept['name']);
        }

        $this->command->info('');
        $this->command->info('Development tenant and organizations seeded successfully!');
        $this->command->info('Tenant ID: '.$tenantId);
        $this->command->info('Organization ID: '.$organizationId);
    }
}
