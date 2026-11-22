<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Tests\Feature\UOM;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\UOM\Constants\UomErrorCode;
use Modules\UOM\Services\UnitOfMeasures\DeleteUnitOfMeasureService;
use Modules\UOM\Services\UnitOfMeasures\UpdateUnitOfMeasureService;
use Tests\Support\OrganizationUnitFixture;
use Tests\TestCase;

final class UnitOfMeasureBlockerTest extends TestCase
{
    use RefreshDatabase;

    public function test_uom_cannot_be_deleted_when_referenced_by_items(): void
    {
        $tenantId = $this->tenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $this->mockTenantAccessor($tenantId);

            $uomId = DB::table('unit_of_measures')->insertGetId([
                'tenant_id' => $tenantId,
                'code' => 'BOX',
                'name' => 'Box',
                'symbol' => 'bx',
                'type' => 'unit',
                'category' => 'count',
                'is_active' => true,
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('items')->insert([
                'tenant_id' => $tenantId,
                'code' => 'ITM-BOX-01',
                'name' => 'Sample Box Item',
                'base_uom_id' => $uomId,
                'item_type' => 'inventory',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $service = app(DeleteUnitOfMeasureService::class);
            $result = $service->execute($uomId);

            $this->assertTrue($result->isFailure());
            $this->assertSame(UomErrorCode::CONFLICT, $result->errorOrFail()->code);
            $this->assertStringContainsString('referenced by items', $result->errorOrFail()->message);
        });
    }

    public function test_uom_cannot_be_deleted_when_referenced_by_conversions(): void
    {
        $tenantId = $this->tenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $this->mockTenantAccessor($tenantId);

            $fromUomId = DB::table('unit_of_measures')->insertGetId([
                'tenant_id' => $tenantId,
                'code' => 'PACK',
                'name' => 'Pack',
                'symbol' => 'pk',
                'type' => 'unit',
                'category' => 'count',
                'is_active' => true,
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $toUomId = DB::table('unit_of_measures')->insertGetId([
                'tenant_id' => $tenantId,
                'code' => 'PIECE',
                'name' => 'Piece',
                'symbol' => 'pc',
                'type' => 'unit',
                'category' => 'count',
                'is_active' => true,
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('uom_conversions')->insert([
                'tenant_id' => $tenantId,
                'from_uom_id' => $fromUomId,
                'to_uom_id' => $toUomId,
                'conversion_factor' => '10.000000',
                'is_active' => true,
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $service = app(DeleteUnitOfMeasureService::class);
            $result = $service->execute($fromUomId);

            $this->assertTrue($result->isFailure());
            $this->assertSame(UomErrorCode::CONFLICT, $result->errorOrFail()->code);
        });
    }

    public function test_unreferenced_uom_can_be_deleted(): void
    {
        $tenantId = $this->tenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $this->mockTenantAccessor($tenantId);

            $uomId = DB::table('unit_of_measures')->insertGetId([
                'tenant_id' => $tenantId,
                'code' => 'UNUSED',
                'name' => 'Unused Unit',
                'symbol' => 'un',
                'type' => 'unit',
                'category' => 'count',
                'is_active' => true,
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $service = app(DeleteUnitOfMeasureService::class);
            $result = $service->execute($uomId);

            $this->assertTrue($result->isSuccess());
            $this->assertSoftDeleted('unit_of_measures', ['id' => $uomId]);
        });
    }

    public function test_uom_update_verifies_optimistic_concurrency(): void
    {
        $tenantId = $this->tenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $this->mockTenantAccessor($tenantId);

            $uomId = DB::table('unit_of_measures')->insertGetId([
                'tenant_id' => $tenantId,
                'code' => 'MTR',
                'name' => 'Meter',
                'symbol' => 'm',
                'type' => 'unit',
                'category' => 'length',
                'is_active' => true,
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $service = app(UpdateUnitOfMeasureService::class);

            // Mismatched expected_version should fail with CONFLICT
            $failResult = $service->execute($uomId, [
                'expected_version' => 99,
                'name' => 'Meter Modified',
            ]);

            $this->assertTrue($failResult->isFailure());
            $this->assertSame(UomErrorCode::CONFLICT, $failResult->errorOrFail()->code);

            // Correct version succeeds and bumps version
            $successResult = $service->execute($uomId, [
                'expected_version' => 1,
                'name' => 'Meter Renamed',
            ]);

            $this->assertTrue($successResult->isSuccess());
            $this->assertDatabaseHas('unit_of_measures', [
                'id' => $uomId,
                'name' => 'Meter Renamed',
                'row_version' => 2,
            ]);
        });
    }

    private function mockTenantAccessor(int $tenantId): void
    {
        $accessor = $this->createMock(CurrentTenantContextAccessorInterface::class);
        $accessor->method('currentTenantId')->willReturn($tenantId);
        $this->app->instance(CurrentTenantContextAccessorInterface::class, $accessor);
    }

    private function tenant(): int
    {
        $now = now();
        $code = Str::upper(Str::random(6));

        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => $code,
            'name' => 'UOM Test Tenant '.$code,
            'slug' => 'uom-test-tenant-'.strtolower($code),
            'status' => 'active',
            'row_version' => 1,
            'status_reason' => 'Testing UOM deletion blockers',
            'status_changed_at' => $now,
            'activated_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        \Tests\Support\ActiveTenantSubscriptionFixture::create($tenantId);

        return $tenantId;
    }
}
