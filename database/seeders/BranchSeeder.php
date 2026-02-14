<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Branch\Models\Branch;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            // Get the manager for this tenant
            $manager = User::where('tenant_id', $tenant->id)
                ->where('email', "manager@{$tenant->subdomain}.com")
                ->first();

            // Create Headquarters
            $headquarters = Branch::firstOrCreate(
                [
                    'code' => strtoupper($tenant->subdomain).'-HQ',
                    'tenant_id' => $tenant->id,
                ],
                [
                    'name' => 'Headquarters',
                    'type' => 'headquarters',
                    'address' => $this->getAddress($tenant->subdomain, 'hq'),
                    'city' => $this->getCity($tenant->subdomain),
                    'state' => $this->getState($tenant->subdomain),
                    'country' => $this->getCountry($tenant->subdomain),
                    'postal_code' => $this->getPostalCode($tenant->subdomain),
                    'phone' => $this->getPhone($tenant->subdomain, 1),
                    'email' => "hq@{$tenant->subdomain}.com",
                    'is_active' => true,
                    'manager_id' => $manager?->id,
                ]
            );

            // Create Branch A
            $branchA = Branch::firstOrCreate(
                [
                    'code' => strtoupper($tenant->subdomain).'-BR-A',
                    'tenant_id' => $tenant->id,
                ],
                [
                    'parent_id' => $headquarters->id,
                    'name' => 'Branch A',
                    'type' => 'branch',
                    'address' => $this->getAddress($tenant->subdomain, 'branch-a'),
                    'city' => $this->getCity($tenant->subdomain),
                    'state' => $this->getState($tenant->subdomain),
                    'country' => $this->getCountry($tenant->subdomain),
                    'postal_code' => $this->getPostalCode($tenant->subdomain, 2),
                    'phone' => $this->getPhone($tenant->subdomain, 2),
                    'email' => "branch-a@{$tenant->subdomain}.com",
                    'is_active' => true,
                ]
            );

            // Create Branch B
            $branchB = Branch::firstOrCreate(
                [
                    'code' => strtoupper($tenant->subdomain).'-BR-B',
                    'tenant_id' => $tenant->id,
                ],
                [
                    'parent_id' => $headquarters->id,
                    'name' => 'Branch B',
                    'type' => 'retail',
                    'address' => $this->getAddress($tenant->subdomain, 'branch-b'),
                    'city' => $this->getCity($tenant->subdomain, true),
                    'state' => $this->getState($tenant->subdomain),
                    'country' => $this->getCountry($tenant->subdomain),
                    'postal_code' => $this->getPostalCode($tenant->subdomain, 3),
                    'phone' => $this->getPhone($tenant->subdomain, 3),
                    'email' => "branch-b@{$tenant->subdomain}.com",
                    'is_active' => true,
                ]
            );

            // Create Warehouse
            $warehouse = Branch::firstOrCreate(
                [
                    'code' => strtoupper($tenant->subdomain).'-WH',
                    'tenant_id' => $tenant->id,
                ],
                [
                    'parent_id' => $headquarters->id,
                    'name' => 'Main Warehouse',
                    'type' => 'warehouse',
                    'address' => $this->getAddress($tenant->subdomain, 'warehouse'),
                    'city' => $this->getCity($tenant->subdomain),
                    'state' => $this->getState($tenant->subdomain),
                    'country' => $this->getCountry($tenant->subdomain),
                    'postal_code' => $this->getPostalCode($tenant->subdomain, 4),
                    'phone' => $this->getPhone($tenant->subdomain, 4),
                    'email' => "warehouse@{$tenant->subdomain}.com",
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Branches created successfully for all tenants!');
    }

    private function getAddress(string $subdomain, string $type): string
    {
        $addresses = [
            'acme' => [
                'hq' => '1234 Corporate Blvd',
                'branch-a' => '5678 Commerce Street',
                'branch-b' => '910 Market Avenue',
                'warehouse' => '1122 Industrial Way',
            ],
            'techstart' => [
                'hq' => '2000 Innovation Drive',
                'branch-a' => '3500 Tech Park',
                'branch-b' => '4200 Silicon Valley Rd',
                'warehouse' => '5000 Distribution Center',
            ],
            'globaltrade' => [
                'hq' => '100 International Plaza',
                'branch-a' => '250 Trade Street',
                'branch-b' => '300 Commerce Lane',
                'warehouse' => '450 Logistics Hub',
            ],
        ];

        return $addresses[$subdomain][$type] ?? '123 Main Street';
    }

    private function getCity(string $subdomain, bool $alternate = false): string
    {
        $cities = [
            'acme' => $alternate ? 'Boston' : 'New York',
            'techstart' => $alternate ? 'San Francisco' : 'San Jose',
            'globaltrade' => $alternate ? 'Manchester' : 'London',
        ];

        return $cities[$subdomain] ?? 'Default City';
    }

    private function getState(string $subdomain): string
    {
        $states = [
            'acme' => 'NY',
            'techstart' => 'CA',
            'globaltrade' => 'England',
        ];

        return $states[$subdomain] ?? 'State';
    }

    private function getCountry(string $subdomain): string
    {
        $countries = [
            'acme' => 'USA',
            'techstart' => 'USA',
            'globaltrade' => 'UK',
        ];

        return $countries[$subdomain] ?? 'USA';
    }

    private function getPostalCode(string $subdomain, int $variant = 1): string
    {
        $baseCodes = [
            'acme' => '10001',
            'techstart' => '95110',
            'globaltrade' => 'SW1A 1AA',
        ];

        $baseCode = $baseCodes[$subdomain] ?? '00000';

        if (is_numeric($baseCode)) {
            return str_pad((int) $baseCode + ($variant - 1), 5, '0', STR_PAD_LEFT);
        }

        return $baseCode;
    }

    private function getPhone(string $subdomain, int $variant): string
    {
        $basePhones = [
            'acme' => '+1-212-555-0',
            'techstart' => '+1-408-555-0',
            'globaltrade' => '+44-20-7946-0',
        ];

        $basePhone = $basePhones[$subdomain] ?? '+1-555-000-0';

        return $basePhone.str_pad($variant, 3, '0', STR_PAD_LEFT);
    }
}
