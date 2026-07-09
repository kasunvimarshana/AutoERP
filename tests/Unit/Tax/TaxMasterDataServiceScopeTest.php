<?php

declare(strict_types=1);

namespace Tests\Unit\Tax;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Tax\Models\CustomerTaxProfile;
use Modules\Tax\Models\SupplierTaxProfile;
use Modules\Tax\Models\Tax;
use Modules\Tax\Models\TaxPostingProfile;
use Modules\Tax\Services\Party\TaxPartyResolverRegistry;
use Modules\Tax\Services\TaxMasterDataService;
use Tests\TestCase;

final class TaxMasterDataServiceScopeTest extends TestCase
{
    public function test_existing_tax_cannot_change_organization_unit_scope(): void
    {
        config()->set('tax.calculation_methods', ['percentage']);

        $tax = new Tax();
        $tax->forceFill([
            'tenant_id' => 1,
            'organization_unit_id' => 10,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tax scope cannot be changed.');

        $this->service()->saveTax([
            'tenant_id' => 1,
            'organization_unit_id' => 20,
            'code' => 'VAT',
            'name' => 'VAT',
            'tax_type' => 'VAT',
            'calculation_method' => 'percentage',
        ], $tax);
    }

    public function test_existing_customer_tax_profile_scope_guard_runs_before_party_resolution(): void
    {
        $profile = new CustomerTaxProfile();
        $profile->forceFill([
            'tenant_id' => 1,
            'organization_unit_id' => 10,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Customer tax profile scope cannot be changed.');

        $this->service()->saveCustomerProfile([
            'tenant_id' => 1,
            'organization_unit_id' => 20,
            'customer_id' => 999,
            'exemption_status' => 'taxable',
        ], $profile);
    }

    public function test_existing_supplier_tax_profile_scope_guard_runs_before_party_resolution(): void
    {
        $profile = new SupplierTaxProfile();
        $profile->forceFill([
            'tenant_id' => 1,
            'organization_unit_id' => 10,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Supplier tax profile scope cannot be changed.');

        $this->service()->saveSupplierProfile([
            'tenant_id' => 1,
            'organization_unit_id' => 20,
            'supplier_id' => 999,
            'exemption_status' => 'taxable',
        ], $profile);
    }

    public function test_existing_tax_posting_profile_scope_guard_runs_before_tax_lookup(): void
    {
        $profile = new TaxPostingProfile();
        $profile->forceFill([
            'tenant_id' => 1,
            'organization_unit_id' => 10,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tax posting profile scope cannot be changed.');

        $this->service()->savePostingProfile([
            'tenant_id' => 1,
            'organization_unit_id' => 20,
            'tax_id' => 999,
            'direction' => 'tax',
            'posting_key' => 'tax.output',
        ], $profile);
    }

    private function service(): TaxMasterDataService
    {
        return new TaxMasterDataService(
            new DecimalMath(),
            new TaxPartyResolverRegistry([]),
        );
    }
}
