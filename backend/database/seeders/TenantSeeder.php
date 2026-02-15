<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Tenant\Models\Tenant;
use App\Modules\Tenant\Models\SubscriptionPlan;
use App\Modules\Tenant\Models\Organization;
use App\Modules\Tenant\Models\Branch;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create subscription plans
        $freePlan = SubscriptionPlan::create([
            'name' => 'Free Trial',
            'slug' => 'free-trial',
            'description' => '30-day free trial with limited features',
            'price' => 0,
            'billing_cycle' => 'monthly',
            'trial_days' => 30,
            'max_users' => 5,
            'max_branches' => 1,
            'max_products' => 100,
            'features' => json_encode([
                'inventory_management' => true,
                'basic_reporting' => true,
                'email_support' => true,
            ]),
            'is_active' => true,
        ]);

        $basicPlan = SubscriptionPlan::create([
            'name' => 'Basic Plan',
            'slug' => 'basic',
            'description' => 'Perfect for small businesses',
            'price' => 29.99,
            'billing_cycle' => 'monthly',
            'trial_days' => 14,
            'max_users' => 10,
            'max_branches' => 3,
            'max_products' => 1000,
            'features' => json_encode([
                'inventory_management' => true,
                'basic_reporting' => true,
                'advanced_reporting' => true,
                'email_support' => true,
                'phone_support' => true,
            ]),
            'is_active' => true,
        ]);

        $proPlan = SubscriptionPlan::create([
            'name' => 'Professional Plan',
            'slug' => 'professional',
            'description' => 'For growing businesses',
            'price' => 99.99,
            'billing_cycle' => 'monthly',
            'trial_days' => 14,
            'max_users' => 50,
            'max_branches' => 10,
            'max_products' => 10000,
            'features' => json_encode([
                'inventory_management' => true,
                'basic_reporting' => true,
                'advanced_reporting' => true,
                'analytics' => true,
                'multi_currency' => true,
                'multi_branch' => true,
                'email_support' => true,
                'phone_support' => true,
                'priority_support' => true,
            ]),
            'is_active' => true,
        ]);

        // Create demo tenant
        $tenant = Tenant::create([
            'name' => 'Demo Company',
            'slug' => 'demo-company',
            'domain' => 'demo.autoerp.local',
            'email' => 'admin@demo.autoerp.local',
            'status' => 'active',
            'subscription_plan_id' => $proPlan->id,
            'trial_ends_at' => now()->addDays(30),
            'subscription_starts_at' => now(),
            'subscription_ends_at' => now()->addYear(),
            'settings' => json_encode([
                'timezone' => 'UTC',
                'currency' => 'USD',
                'date_format' => 'Y-m-d',
                'time_format' => 'H:i:s',
            ]),
        ]);

        // Create main organization
        $organization = Organization::create([
            'tenant_id' => $tenant->id,
            'name' => 'Demo Company HQ',
            'code' => 'HQ',
            'parent_id' => null,
            'status' => 'active',
        ]);

        // Create branches
        Branch::create([
            'tenant_id' => $tenant->id,
            'organization_id' => $organization->id,
            'name' => 'Main Warehouse',
            'code' => 'WH-MAIN',
            'is_warehouse' => true,
            'is_store' => false,
            'address' => '123 Main Street',
            'city' => 'New York',
            'state' => 'NY',
            'country' => 'USA',
            'postal_code' => '10001',
            'status' => 'active',
        ]);

        Branch::create([
            'tenant_id' => $tenant->id,
            'organization_id' => $organization->id,
            'name' => 'Retail Store 1',
            'code' => 'STORE-01',
            'is_warehouse' => false,
            'is_store' => true,
            'address' => '456 Shopping Plaza',
            'city' => 'New York',
            'state' => 'NY',
            'country' => 'USA',
            'postal_code' => '10002',
            'status' => 'active',
        ]);
    }
}
