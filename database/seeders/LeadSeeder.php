<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Sales\Entities\Customer;
use Modules\Sales\Entities\Lead;

/**
 * Lead Seeder
 *
 * Seeds lead data with various statuses and stages.
 */
class LeadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding leads...');

        // Get some customers for converted leads
        $customers = Customer::where('tenant_id', 1)->limit(3)->get();

        // Create new leads
        Lead::factory()->newLead()->count(3)->create([
            'tenant_id' => 1,
        ]);

        // Create qualified leads
        Lead::factory()->qualified()->count(4)->create([
            'tenant_id' => 1,
        ]);

        // Create hot leads in negotiation
        Lead::factory()->hot()->negotiation()->count(3)->create([
            'tenant_id' => 1,
        ]);

        // Create won leads
        Lead::factory()->won()->count(4)->create([
            'tenant_id' => 1,
        ]);

        // Create lost leads
        Lead::factory()->lost()->count(3)->create([
            'tenant_id' => 1,
        ]);

        // Create converted leads (if customers exist)
        if ($customers->count() > 0) {
            foreach ($customers as $customer) {
                Lead::factory()->converted()->create([
                    'tenant_id' => 1,
                    'customer_id' => $customer->id,
                ]);
            }
        }

        // Create website leads
        Lead::factory()->fromWebsite()->count(2)->create([
            'tenant_id' => 1,
        ]);

        // Create referral leads
        Lead::factory()->fromReferral()->count(2)->create([
            'tenant_id' => 1,
        ]);

        $this->command->info('Leads seeded successfully!');
    }
}
