<?php

declare(strict_types=1);

namespace Modules\VehicleService\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\VehicleService\Enums\VehicleServiceCommissionType;
use Modules\VehicleService\Services\VehicleServiceCommissionPolicyService;
use Tests\Support\OrganizationUnitFixture;
use Tests\TestCase;

final class VehicleServiceCommissionPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervisor_default_is_normalized_versioned_and_conflict_aware(): void
    {
        $context = $this->context();

        $first = $this->withTenantExecutionContext(
            $context['tenant_id'],
            fn () => app(VehicleServiceCommissionPolicyService::class)->saveSupervisorDefault(
                $context['tenant_id'],
                $context['organization_unit_id'],
                VehicleServiceCommissionType::Percentage,
                '5',
                true,
                null,
                null,
            ),
        );

        $this->assertSame(1, (int) $first->row_version);
        $this->assertSame('5.000000', (string) $first->commission_value);
        $resolved = $this->withTenantExecutionContext(
            $context['tenant_id'],
            fn () => app(VehicleServiceCommissionPolicyService::class)->resolveSupervisorDefault(
                $context['tenant_id'],
                $context['organization_unit_id'],
            ),
        );
        $this->assertSame(VehicleServiceCommissionType::Percentage, $resolved['type']);
        $this->assertSame('5.000000', $resolved['value']);

        $second = $this->withTenantExecutionContext(
            $context['tenant_id'],
            fn () => app(VehicleServiceCommissionPolicyService::class)->saveSupervisorDefault(
                $context['tenant_id'],
                $context['organization_unit_id'],
                VehicleServiceCommissionType::Fixed,
                '250',
                true,
                1,
                null,
            ),
        );
        $this->assertSame(2, (int) $second->row_version);
        $this->assertSame('250.000000', (string) $second->commission_value);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The commission policy changed since it was loaded.');
        $this->withTenantExecutionContext(
            $context['tenant_id'],
            fn () => app(VehicleServiceCommissionPolicyService::class)->saveSupervisorDefault(
                $context['tenant_id'],
                $context['organization_unit_id'],
                VehicleServiceCommissionType::Percentage,
                '7.5',
                true,
                1,
                null,
            ),
        );
    }

    public function test_labor_item_has_one_versioned_default_independent_of_uom_kind(): void
    {
        $context = $this->context();
        $unitUomId = $this->uom($context, 'JOB', 'Job', 'service');
        $laborItemId = $this->item($context, 'LAB-JOB', 'Job-based labour', 'labour', $unitUomId);

        $first = $this->withTenantExecutionContext(
            $context['tenant_id'],
            fn () => app(VehicleServiceCommissionPolicyService::class)->saveLaborRule(
                $context['tenant_id'],
                $context['organization_unit_id'],
                $laborItemId,
                VehicleServiceCommissionType::Percentage,
                '10',
                true,
                null,
                null,
            ),
        );

        $this->assertSame(1, (int) $first->row_version);
        $this->assertSame('10.000000', (string) $first->commission_value);
        $this->assertSame($unitUomId, (int) $first->item->base_uom_id);

        $resolved = $this->withTenantExecutionContext(
            $context['tenant_id'],
            fn () => app(VehicleServiceCommissionPolicyService::class)->resolveLaborRule(
                $context['tenant_id'],
                $context['organization_unit_id'],
                $laborItemId,
            ),
        );
        $this->assertSame(VehicleServiceCommissionType::Percentage, $resolved['type']);
        $this->assertSame('10.000000', $resolved['value']);

        $second = $this->withTenantExecutionContext(
            $context['tenant_id'],
            fn () => app(VehicleServiceCommissionPolicyService::class)->saveLaborRule(
                $context['tenant_id'],
                $context['organization_unit_id'],
                $laborItemId,
                VehicleServiceCommissionType::Fixed,
                '500',
                true,
                1,
                null,
            ),
        );
        $this->assertSame($first->getKey(), $second->getKey());
        $this->assertSame(2, (int) $second->row_version);
        $this->assertSame('500.000000', (string) $second->commission_value);

        $defaults = $this->withTenantExecutionContext(
            $context['tenant_id'],
            fn () => app(VehicleServiceCommissionPolicyService::class)->laborDefaultsForItems(
                $context['tenant_id'],
                $context['organization_unit_id'],
                [$laborItemId],
            ),
        );
        $this->assertSame('fixed', $defaults[$laborItemId]['commission_type']);
        $this->assertSame('500.000000', $defaults[$laborItemId]['commission_value']);
    }

    public function test_non_labor_items_cannot_own_labor_commission_rules(): void
    {
        $context = $this->context();
        $unitUomId = $this->uom($context, 'UNIT', 'Unit', 'unit');
        $stockItemId = $this->item($context, 'PART-001', 'Stock part', 'stock', $unitUomId);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Commission defaults can only be assigned to labour items.');
        $this->withTenantExecutionContext(
            $context['tenant_id'],
            fn () => app(VehicleServiceCommissionPolicyService::class)->saveLaborRule(
                $context['tenant_id'],
                $context['organization_unit_id'],
                $stockItemId,
                VehicleServiceCommissionType::Fixed,
                '100',
                true,
                null,
                null,
            ),
        );
    }

    /** @return array{tenant_id:int, organization_unit_id:int} */
    private function context(): array
    {
        $now = now();
        $code = 'VSC-'.Str::upper(Str::random(6));
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => $code,
            'name' => $code.' Tenant',
            'slug' => Str::lower($code).'-tenant',
            'status' => 'active',
            'row_version' => 1,
            'status_reason' => 'Vehicle Service commission policy test.',
            'status_changed_at' => $now,
            'activated_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $organizationUnitId = (int) OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => 'Main Workshop',
            'code' => 'WORKSHOP',
            'path' => '/workshop',
            'is_active' => true,
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
        ];
    }

    /** @param array{tenant_id:int, organization_unit_id:int} $context */
    private function uom(array $context, string $code, string $name, string $type): int
    {
        return (int) DB::table('unit_of_measures')->insertGetId([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'row_version' => 1,
            'code' => $code,
            'name' => $name,
            'symbol' => $code,
            'type' => $type,
            'category' => $type === 'service' ? 'service' : 'quantity',
            'decimal_precision' => 6,
            'allow_fractional_quantity' => true,
            'is_base' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @param array{tenant_id:int, organization_unit_id:int} $context */
    private function item(array $context, string $code, string $name, string $type, int $uomId): int
    {
        return (int) DB::table('items')->insertGetId([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'code' => $code,
            'name' => $name,
            'item_type' => $type,
            'tracking_type' => 'none',
            'costing_method' => 'none',
            'base_uom_id' => $uomId,
            'is_stockable' => false,
            'is_combo' => false,
            'is_tax_exempt' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
