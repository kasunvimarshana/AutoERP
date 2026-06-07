<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class BooleanQueryFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    public function test_warehouse_boolean_filters_accept_supported_query_representations(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();
        $this->warehouse($tenantId, $organizationUnitId, 'ACTIVE', true);
        $this->warehouse($tenantId, $organizationUnitId, 'INACTIVE', false);

        foreach (['true', '1'] as $value) {
            $this->getJson($this->warehouseUrl($tenantId, $organizationUnitId, $value))
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.code', 'ACTIVE')
                ->assertJsonPath('meta.current_page', 1)
                ->assertJsonPath('meta.last_page', 1);
        }

        foreach (['false', '0'] as $value) {
            $this->getJson($this->warehouseUrl($tenantId, $organizationUnitId, $value))
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.code', 'INACTIVE');
        }

        $this->getJson($this->warehouseUrl($tenantId, $organizationUnitId, 'yes'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('is_active');
    }

    public function test_warehouse_record_access_is_tenant_scoped(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();
        [$otherTenantId, $otherOrganizationUnitId] = $this->scope();
        $sameTenantOtherOrganizationId = $this->organization($tenantId);
        $warehouseId = $this->warehouse($tenantId, $organizationUnitId, 'PRIVATE', true);

        $scope = '?tenant_id='.$otherTenantId.'&organization_unit_id='.$otherOrganizationUnitId;

        $this->getJson('/api/v1/warehouses/'.$warehouseId.$scope)->assertNotFound();
        $this->patchJson('/api/v1/warehouses/'.$warehouseId, [
            'tenant_id' => $otherTenantId,
            'organization_unit_id' => $otherOrganizationUnitId,
            'name' => 'Cross tenant update',
        ])->assertNotFound();
        $this->deleteJson('/api/v1/warehouses/'.$warehouseId.$scope)->assertNotFound();

        $organizationScope = '?tenant_id='.$tenantId.'&organization_unit_id='.$sameTenantOtherOrganizationId;

        $this->getJson('/api/v1/warehouses/'.$warehouseId.$organizationScope)->assertNotFound();
        $this->patchJson('/api/v1/warehouses/'.$warehouseId, [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $sameTenantOtherOrganizationId,
            'name' => 'Cross organization update',
        ])->assertNotFound();
        $this->deleteJson('/api/v1/warehouses/'.$warehouseId.$organizationScope)->assertNotFound();
    }

    private function warehouseUrl(int $tenantId, int $organizationUnitId, string $isActive): string
    {
        return '/api/v1/warehouses?tenant_id='.$tenantId
            .'&organization_unit_id='.$organizationUnitId
            .'&is_active='.$isActive;
    }

    private function warehouse(int $tenantId, int $organizationUnitId, string $code, bool $isActive): int
    {
        return (int) $this->postJson('/api/v1/warehouses', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'code' => $code,
            'name' => $code.' Warehouse',
            'type' => 'standard',
            'is_active' => $isActive,
        ])->assertCreated()->json('data.id');
    }

    /**
     * @return array{int, int}
     */
    private function scope(): array
    {
        $suffix = Str::upper(Str::random(8));
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-'.$suffix,
            'name' => 'Tenant '.$suffix,
            'slug' => 'tenant-'.Str::lower($suffix),
            'status' => 'active',
            'is_active' => true,
            'is_isolated' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$tenantId, $this->organization($tenantId, $suffix)];
    }

    private function organization(int $tenantId, ?string $suffix = null): int
    {
        $suffix ??= Str::upper(Str::random(8));

        return (int) DB::table('organization_units')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Organization '.$suffix,
            'code' => 'ORG-'.$suffix,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
