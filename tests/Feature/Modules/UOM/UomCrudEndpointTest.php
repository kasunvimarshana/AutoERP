<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\UOM;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class UomCrudEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_uom_unit_and_conversion_crud_work_with_backend_context(): void
    {
        [$tenantId, $organizationUnitId, $headers] = $this->authenticatedHeaders();

        $createUnit = $this->withHeaders($headers)->postJson('/api/uom/units-of-measure', [
            'code' => 'TST',
            'name' => 'Test Unit',
            'symbol' => 'tst',
            'type' => 'UNIT',
            'category' => 'UNIT',
            'decimal_precision' => 0,
            'allow_fractional_quantity' => false,
            'is_base' => false,
            'usable_for_purchase' => true,
            'usable_for_sales' => true,
            'usable_for_inventory' => true,
            'usable_for_service' => true,
            'usable_for_rental' => false,
            'is_active' => true,
        ]);

        $createUnit
            ->assertCreated()
            ->assertJsonPath('data.code', 'TST')
            ->assertJsonPath('data.tenant_id', $tenantId)
            ->assertJsonPath('data.organization_unit_id', $organizationUnitId);

        $unitId = (int) $createUnit->json('data.id');
        $pcsId = (int) DB::table('unit_of_measures')
            ->where('tenant_id', $tenantId)
            ->where('code', 'PCS')
            ->value('id');

        $this->withHeaders($headers)
            ->getJson('/api/uom/units-of-measure?search=TST')
            ->assertOk()
            ->assertJsonPath('data.0.id', $unitId);

        $this->withHeaders($headers)
            ->putJson('/api/uom/units-of-measure/'.$unitId, [
                'code' => 'TST',
                'name' => 'Test Unit Updated',
                'symbol' => 'tst',
                'type' => 'UNIT',
                'category' => 'UNIT',
                'decimal_precision' => 0,
                'allow_fractional_quantity' => false,
                'is_base' => false,
                'usable_for_purchase' => true,
                'usable_for_sales' => true,
                'usable_for_inventory' => true,
                'usable_for_service' => true,
                'usable_for_rental' => false,
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Test Unit Updated')
            ->assertJsonPath('data.is_active', false);

        $createConversion = $this->withHeaders($headers)->postJson('/api/uom/uom-conversions', [
            'from_uom_id' => $unitId,
            'to_uom_id' => $pcsId,
            'factor' => '3',
            'is_bidirectional' => true,
            'is_active' => true,
        ]);

        $createConversion
            ->assertCreated()
            ->assertJsonPath('data.from_uom_id', $unitId)
            ->assertJsonPath('data.to_uom_id', $pcsId);

        $conversionId = (int) $createConversion->json('data.id');

        $previewResponse = $this->withHeaders($headers)
            ->postJson('/api/uom/convert', [
                'quantity' => 2,
                'from_uom_id' => $unitId,
                'to_uom_id' => $pcsId,
            ])
            ->assertOk();

        self::assertSame(6.0, (float) $previewResponse->json('calculated.converted_quantity'));
        self::assertSame(3.0, (float) $previewResponse->json('calculated.factor'));

        $this->withHeaders($headers)
            ->putJson('/api/uom/uom-conversions/'.$conversionId, [
                'from_uom_id' => $unitId,
                'to_uom_id' => $pcsId,
                'factor' => '4',
                'is_bidirectional' => true,
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.factor', '4.00000000')
            ->assertJsonPath('data.is_active', false);
    }

    public function test_uom_validation_errors_are_returned(): void
    {
        [, , $headers] = $this->authenticatedHeaders();

        $this->withHeaders($headers)
            ->postJson('/api/uom/units-of-measure', ['code' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code', 'name', 'symbol']);
    }

    /**
     * @return array{0:int,1:int,2:array<string,string>}
     */
    private function authenticatedHeaders(): array
    {
        $tenantId = (int) DB::table('tenants')->where('code', 'AUTOERP')->value('id');
        $organizationUnitId = (int) DB::table('organization_units')
            ->where('tenant_id', $tenantId)
            ->where('code', 'MAIN')
            ->value('id');

        $loginResponse = $this->postJson('/api/auth/login', [
            'login_identifier' => 'admin@example.com',
            'password' => 'password',
            'provider_key' => 'internal',
            'tenant_id' => $tenantId,
        ]);

        $loginResponse->assertOk();

        return [
            $tenantId,
            $organizationUnitId,
            [
                'Authorization' => 'Bearer '.(string) $loginResponse->json('data.tokens.access_token'),
                'X-Organization-Unit-ID' => (string) $organizationUnitId,
                'X-Tenant-ID' => (string) $tenantId,
            ],
        ];
    }
}
