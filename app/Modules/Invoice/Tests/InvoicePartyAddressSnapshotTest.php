<?php

declare(strict_types=1);

namespace Modules\Invoice\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoicePartyType;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Services\InvoiceReferenceSnapshotService;
use Tests\Support\OrganizationUnitFixture;
use Tests\TestCase;

final class InvoicePartyAddressSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_exact_organization_address_precedes_tenant_fallback(): void
    {
        $suffix = Str::upper(Str::random(6));
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-'.$suffix,
            'name' => 'Tenant '.$suffix,
            'slug' => 'tenant-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $organizationUnitId = OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => 'Address Scope Unit',
            'code' => 'ADDR-'.$suffix,
        ]);
        $customerId = (int) DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'customer_number' => 'CUS-'.$suffix,
            'code' => 'CUS-'.$suffix,
            'name' => 'Address Scope Customer',
            'legal_name' => 'Address Scope Customer Legal',
            'display_name' => 'Address Scope Customer',
            'customer_type' => 'company',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('customer_addresses')->insert([
            [
                'tenant_id' => $tenantId,
                'organization_unit_id' => null,
                'customer_id' => $customerId,
                'address_type' => 'registered',
                'address_line_1' => 'Tenant Fallback Address',
                'is_primary' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'customer_id' => $customerId,
                'address_type' => 'billing',
                'address_line_1' => 'Exact Organization Address',
                'is_primary' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $party = $this->withTenantExecutionContext(
            $tenantId,
            fn (): array => app(InvoiceReferenceSnapshotService::class)->documentParty(new CreateInvoiceData(
                tenantId: $tenantId,
                invoiceType: InvoiceType::Manual,
                direction: InvoiceDirection::Outbound,
                invoiceDate: '2026-07-21',
                organizationUnitId: $organizationUnitId,
                partyType: InvoicePartyType::Customer->value,
                partyId: $customerId,
            )),
        );

        self::assertSame('Exact Organization Address', $party['address']);
    }
}
