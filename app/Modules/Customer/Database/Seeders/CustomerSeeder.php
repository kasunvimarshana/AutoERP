<?php

declare(strict_types=1);

namespace Modules\Customer\Database\Seeders;

use Database\Seeders\Concerns\ResolvesSeedContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerCategory;
use Modules\Customer\Models\CustomerCategoryAssignment;
use Modules\Customer\Models\CustomerCreditProfile;
use Modules\Sequence\Models\SequenceModel;
use Modules\Sequence\Services\Contracts\SequenceDomainServiceInterface;

final class CustomerSeeder extends Seeder
{
    use ResolvesSeedContext;

    public function run(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        $tenant = $this->defaultTenant();
        $organizationUnit = $this->defaultOrganizationUnit($tenant);
        if ($tenant === null) {
            return;
        }

        DB::transaction(function () use ($tenant, $organizationUnit): void {
            $category = CustomerCategory::query()->updateOrCreate(
                ['tenant_id' => $tenant->getKey(), 'code' => 'GENERAL'],
                [
                    'organization_unit_id' => $organizationUnit?->getKey(),
                    'parent_id' => null,
                    'name' => 'General Customers',
                    'description' => 'Default customer category.',
                    'is_active' => true,
                    'sort_order' => 1,
                ],
            );

            $customer = Customer::query()->updateOrCreate(
                ['tenant_id' => $tenant->getKey(), 'customer_number' => 'CUS-000001'],
                [
                    'organization_unit_id' => $organizationUnit?->getKey(),
                    'code' => 'CUS-WALKIN',
                    'name' => 'Walk-in Customer',
                    'display_name' => 'Walk-in Customer',
                    'customer_type' => 'retail',
                    'status' => 'active',
                    'email' => 'customer@example.com',
                    'default_currency_id' => $this->defaultCurrency()?->getKey(),
                    'is_tax_exempt' => false,
                    'marketing_consent' => false,
                    'preferred_communication_channel' => 'email',
                    'notes' => 'Default customer for local development and testing.',
                    'metadata' => ['seed_source' => 'customer_module'],
                ],
            );

            CustomerCreditProfile::query()->updateOrCreate(
                ['tenant_id' => $tenant->getKey(), 'customer_id' => $customer->getKey()],
                [
                    'organization_unit_id' => $organizationUnit?->getKey(),
                    'row_version' => 1,
                    'credit_limit' => '0.000000',
                    'credit_period_days' => null,
                    'warning_threshold_percent' => '80.000000',
                    'credit_allowed' => false,
                    'advance_allowed' => true,
                    'allow_over_credit' => false,
                    'allow_partial_payment' => true,
                    'is_active' => true,
                ],
            );

            if (Schema::hasTable('customer_category_assignments')) {
                CustomerCategoryAssignment::query()->updateOrCreate(
                    [
                        'customer_id' => $customer->getKey(),
                        'customer_category_id' => $category->getKey(),
                    ],
                    [
                        'tenant_id' => $tenant->getKey(),
                        'organization_unit_id' => $organizationUnit?->getKey(),
                    ],
                );
            }

            if (Schema::hasTable('sequences')) {
                $scopeKey = app(SequenceDomainServiceInterface::class)->scopeKey(null, null);

                SequenceModel::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenant->getKey(),
                        'document_type' => 'customer',
                        'scope_key' => $scopeKey,
                    ],
                    [
                        'organization_unit_id' => null,
                        'prefix' => 'CUS-',
                        'suffix' => '',
                        'padding' => 6,
                        'next_number' => 2,
                        'period_type' => 'infinite',
                        'period_value' => null,
                        'row_version' => 1,
                        'metadata' => ['seed_source' => 'customer_module'],
                    ],
                );
            }
        }, 3);
    }
}
