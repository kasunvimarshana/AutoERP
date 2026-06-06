<?php

declare(strict_types=1);

namespace Modules\UOM\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class UomSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('unit_of_measures')) {
            return;
        }

        DB::transaction(function (): void {
            $tenantId = $this->defaultTenantId();
            $organizationUnitId = $this->defaultOrganizationUnitId($tenantId);

            $this->seedUnits($tenantId, $organizationUnitId);
            $this->seedConversions($tenantId, $organizationUnitId);
        }, 3);
    }

    private function seedUnits(int $tenantId, ?int $organizationUnitId): void
    {
        $units = [
            ['code' => 'PCS', 'name' => 'Each', 'symbol' => 'pcs', 'type' => 'UNIT', 'is_base' => true, 'precision' => 0, 'fractional' => false],
            ['code' => 'BOX', 'name' => 'Box', 'symbol' => 'box', 'type' => 'UNIT', 'is_base' => false, 'precision' => 0, 'fractional' => false],
            ['code' => 'PACK', 'name' => 'Pack', 'symbol' => 'pack', 'type' => 'UNIT', 'is_base' => false, 'precision' => 0, 'fractional' => false],
            ['code' => 'KG', 'name' => 'Kilogram', 'symbol' => 'kg', 'type' => 'MASS', 'is_base' => true, 'precision' => 3, 'fractional' => true],
            ['code' => 'G', 'name' => 'Gram', 'symbol' => 'g', 'type' => 'MASS', 'is_base' => false, 'precision' => 3, 'fractional' => true],
            ['code' => 'L', 'name' => 'Liter', 'symbol' => 'l', 'type' => 'VOLUME', 'is_base' => true, 'precision' => 3, 'fractional' => true],
            ['code' => 'ML', 'name' => 'Milliliter', 'symbol' => 'ml', 'type' => 'VOLUME', 'is_base' => false, 'precision' => 3, 'fractional' => true],
            ['code' => 'HOUR', 'name' => 'Hour', 'symbol' => 'hr', 'type' => 'TIME', 'is_base' => true, 'precision' => 2, 'fractional' => true],
            ['code' => 'DAY', 'name' => 'Day', 'symbol' => 'day', 'type' => 'TIME', 'is_base' => false, 'precision' => 2, 'fractional' => true],
            ['code' => 'MONTH', 'name' => 'Month', 'symbol' => 'mo', 'type' => 'TIME', 'is_base' => false, 'precision' => 2, 'fractional' => true],
            ['code' => 'KM', 'name' => 'Kilometer', 'symbol' => 'km', 'type' => 'DISTANCE', 'is_base' => true, 'precision' => 2, 'fractional' => true],
        ];

        foreach ($units as $unit) {
            DB::table('unit_of_measures')->updateOrInsert(
                [
                    'tenant_id' => $tenantId,
                    'code' => $unit['code'],
                ],
                [
                    'allow_fractional_quantity' => $unit['fractional'],
                    'category' => $unit['type'],
                    'decimal_precision' => $unit['precision'],
                    'description' => 'Default UOM for real backend-connected module testing.',
                    'is_active' => true,
                    'is_base' => $unit['is_base'],
                    'metadata' => $this->json(['seed_source' => 'uom_module']),
                    'name' => $unit['name'],
                    'organization_unit_id' => $organizationUnitId,
                    'row_version' => 1,
                    'symbol' => $unit['symbol'],
                    'type' => $unit['type'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }

    private function seedConversions(int $tenantId, ?int $organizationUnitId): void
    {
        if (! Schema::hasTable('uom_conversions')) {
            return;
        }

        $conversions = [
            ['from' => 'BOX', 'to' => 'PCS', 'factor' => '12'],
            ['from' => 'PACK', 'to' => 'PCS', 'factor' => '6'],
            ['from' => 'KG', 'to' => 'G', 'factor' => '1000'],
            ['from' => 'L', 'to' => 'ML', 'factor' => '1000'],
            ['from' => 'DAY', 'to' => 'HOUR', 'factor' => '24'],
            ['from' => 'MONTH', 'to' => 'DAY', 'factor' => '30'],
        ];

        foreach ($conversions as $conversion) {
            $from = $this->unit($tenantId, $conversion['from']);
            $to = $this->unit($tenantId, $conversion['to']);
            if ($from === null || $to === null) {
                continue;
            }

            DB::table('uom_conversions')->updateOrInsert(
                [
                    'tenant_id' => $tenantId,
                    'from_uom_id' => $from->id,
                    'to_uom_id' => $to->id,
                ],
                [
                    'category' => $from->type,
                    'factor' => $conversion['factor'],
                    'is_active' => true,
                    'is_bidirectional' => true,
                    'metadata' => $this->json(['seed_source' => 'uom_module']),
                    'notes' => 'Default generic conversion.',
                    'organization_unit_id' => $organizationUnitId,
                    'row_version' => 1,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }

    private function defaultTenantId(): int
    {
        $code = strtoupper(trim((string) env('AUTH_LOCAL_TENANT_CODE', 'AUTOERP')));
        $id = DB::table('tenants')->where('code', $code)->value('id')
            ?? DB::table('tenants')->orderBy('id')->value('id');

        if ($id === null) {
            throw new RuntimeException('Seed a tenant before running the UOM module seeder.');
        }

        return (int) $id;
    }

    private function defaultOrganizationUnitId(int $tenantId): ?int
    {
        return DB::table('organization_units')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('is_active')
            ->orderBy('id')
            ->value('id');
    }

    private function unit(int $tenantId, string $code): ?object
    {
        return DB::table('unit_of_measures')
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();
    }

    /**
     * @param  array<string,mixed>  $value
     */
    private function json(array $value): string
    {
        return (string) json_encode($value, JSON_THROW_ON_ERROR);
    }
}
