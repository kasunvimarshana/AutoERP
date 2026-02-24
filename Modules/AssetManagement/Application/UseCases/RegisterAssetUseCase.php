<?php

namespace Modules\AssetManagement\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\AssetManagement\Domain\Contracts\AssetRepositoryInterface;
use Modules\AssetManagement\Domain\Events\AssetAcquired;

class RegisterAssetUseCase
{
    public function __construct(
        private AssetRepositoryInterface $assetRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $tenantId = $data['tenant_id'];

            $purchaseCost    = bcadd((string) ($data['purchase_cost'] ?? '0'), '0', 8);
            $salvageValue    = bcadd((string) ($data['salvage_value'] ?? '0'), '0', 8);
            $depreciableBase = bcsub($purchaseCost, $salvageValue, 8);
            $usefulLifeYears = (int) ($data['useful_life_years'] ?? 0);

            // Annual depreciation (straight-line default) using BCMath
            $annualDepreciation = '0.00000000';
            if ($usefulLifeYears > 0 && bccomp($depreciableBase, '0', 8) > 0) {
                $annualDepreciation = bcdiv($depreciableBase, (string) $usefulLifeYears, 8);
            }

            $asset = $this->assetRepo->create([
                'tenant_id'            => $tenantId,
                'asset_category_id'    => $data['asset_category_id'] ?? null,
                'name'                 => $data['name'],
                'description'          => $data['description'] ?? null,
                'serial_number'        => $data['serial_number'] ?? null,
                'location'             => $data['location'] ?? null,
                'purchase_date'        => $data['purchase_date'] ?? null,
                'purchase_cost'        => $purchaseCost,
                'salvage_value'        => $salvageValue,
                'useful_life_years'    => $usefulLifeYears,
                'depreciation_method'  => $data['depreciation_method'] ?? 'straight_line',
                'annual_depreciation'  => $annualDepreciation,
                'book_value'           => $purchaseCost,
                'status'               => 'active',
            ]);

            Event::dispatch(new AssetAcquired($asset->id, $tenantId));

            return $asset;
        });
    }
}
