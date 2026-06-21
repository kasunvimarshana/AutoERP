<?php

declare(strict_types=1);

namespace Modules\UOM\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Database\Seeders\Concerns\ResolvesSeedContext;
use Modules\UOM\Models\UnitOfMeasureModel;
use Modules\UOM\Models\UomConversionModel;

final class UomSeeder extends Seeder
{
    use ResolvesSeedContext;

    public function run(): void
    {
        if (! Schema::hasTable('unit_of_measures')) {
            return;
        }

        $tenant = $this->defaultTenant();
        $organizationUnit = $this->defaultOrganizationUnit($tenant);
        if ($tenant === null) {
            return;
        }

        DB::transaction(function () use ($tenant, $organizationUnit): void {
            foreach ($this->units() as $unit) {
                UnitOfMeasureModel::query()->updateOrCreate(
                    ['tenant_id' => $tenant->getKey(), 'code' => $unit['code']],
                    [
                        'organization_unit_id' => $organizationUnit?->getKey(),
                        'name' => $unit['name'],
                        'symbol' => $unit['symbol'],
                        'type' => $unit['type'],
                        'category' => $unit['category'],
                        'decimal_precision' => $unit['precision'],
                        'allow_fractional_quantity' => $unit['fractional'],
                        'is_base' => $unit['is_base'],
                        'is_active' => true,
                        'description' => 'Default AutoERP unit of measure.',
                        'row_version' => 1,
                        'metadata' => ['seed_source' => 'uom_module'],
                    ],
                );
            }

            $this->seedConversions((int) $tenant->getKey(), $organizationUnit?->getKey());
        }, 3);
    }

    private function seedConversions(int $tenantId, ?int $organizationUnitId): void
    {
        if (! Schema::hasTable('uom_conversions')) {
            return;
        }

        foreach ([
            ['from' => 'KG', 'to' => 'G', 'factor' => '1000.000000'],
            ['from' => 'LTR', 'to' => 'ML', 'factor' => '1000.000000'],
            ['from' => 'M', 'to' => 'CM', 'factor' => '100.000000'],
        ] as $conversion) {
            $from = UnitOfMeasureModel::query()->where('tenant_id', $tenantId)->where('code', $conversion['from'])->first();
            $to = UnitOfMeasureModel::query()->where('tenant_id', $tenantId)->where('code', $conversion['to'])->first();
            if ($from === null || $to === null) {
                continue;
            }

            UomConversionModel::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'from_uom_id' => $from->getKey(),
                    'to_uom_id' => $to->getKey(),
                ],
                [
                    'organization_unit_id' => $organizationUnitId,
                    'conversion_factor' => $conversion['factor'],
                    'is_active' => true,
                    'description' => 'Default generic conversion.',
                    'row_version' => 1,
                ],
            );
        }
    }

    /**
     * @return list<array{code:string,name:string,symbol:string,type:string,category:string,precision:int,fractional:bool,is_base:bool}>
     */
    private function units(): array
    {
        return [
            ['code' => 'PCS', 'name' => 'Pieces', 'symbol' => 'pcs', 'type' => 'unit', 'category' => 'quantity', 'precision' => 0, 'fractional' => false, 'is_base' => true],
            ['code' => 'BOX', 'name' => 'Box', 'symbol' => 'box', 'type' => 'unit', 'category' => 'quantity', 'precision' => 0, 'fractional' => false, 'is_base' => false],
            ['code' => 'KG', 'name' => 'Kilogram', 'symbol' => 'kg', 'type' => 'weight', 'category' => 'weight', 'precision' => 3, 'fractional' => true, 'is_base' => true],
            ['code' => 'G', 'name' => 'Gram', 'symbol' => 'g', 'type' => 'weight', 'category' => 'weight', 'precision' => 3, 'fractional' => true, 'is_base' => false],
            ['code' => 'LTR', 'name' => 'Liter', 'symbol' => 'L', 'type' => 'volume', 'category' => 'volume', 'precision' => 3, 'fractional' => true, 'is_base' => true],
            ['code' => 'ML', 'name' => 'Milliliter', 'symbol' => 'mL', 'type' => 'volume', 'category' => 'volume', 'precision' => 3, 'fractional' => true, 'is_base' => false],
            ['code' => 'M', 'name' => 'Meter', 'symbol' => 'm', 'type' => 'length', 'category' => 'length', 'precision' => 3, 'fractional' => true, 'is_base' => true],
            ['code' => 'CM', 'name' => 'Centimeter', 'symbol' => 'cm', 'type' => 'length', 'category' => 'length', 'precision' => 3, 'fractional' => true, 'is_base' => false],
            ['code' => 'HOUR', 'name' => 'Hour', 'symbol' => 'hr', 'type' => 'time', 'category' => 'time', 'precision' => 2, 'fractional' => true, 'is_base' => true],
            ['code' => 'DAY', 'name' => 'Day', 'symbol' => 'day', 'type' => 'time', 'category' => 'time', 'precision' => 2, 'fractional' => true, 'is_base' => false],
        ];
    }
}
