<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Rental\Application\Contracts\CreateAssetServiceInterface;
use Modules\Rental\Domain\Entities\Asset;
use Modules\Rental\Domain\Exceptions\AssetNotAvailableException;
use Modules\Rental\Domain\RepositoryInterfaces\AssetRepositoryInterface;

class CreateAssetService implements CreateAssetServiceInterface
{
    public function __construct(
        private readonly AssetRepositoryInterface $assetRepository,
    ) {}

    public function execute(array $data): Asset
    {
        if ($this->assetRepository->existsByCode((int) $data['tenant_id'], (string) $data['asset_code'])) {
            throw new AssetNotAvailableException(0, "Asset code '{$data['asset_code']}' already exists.");
        }

        $asset = new Asset(
            tenantId: (int) $data['tenant_id'],
            assetCode: (string) $data['asset_code'],
            assetName: (string) $data['asset_name'],
            usageMode: (string) $data['usage_mode'],
            lifecycleStatus: $data['lifecycle_status'] ?? 'active',
            rentalStatus: $data['rental_status'] ?? 'available',
            serviceStatus: $data['service_status'] ?? 'available',
            orgUnitId: isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null,
            productId: isset($data['product_id']) ? (int) $data['product_id'] : null,
            serialId: isset($data['serial_id']) ? (int) $data['serial_id'] : null,
            supplierId: isset($data['supplier_id']) ? (int) $data['supplier_id'] : null,
            warehouseId: isset($data['warehouse_id']) ? (int) $data['warehouse_id'] : null,
            currencyId: isset($data['currency_id']) ? (int) $data['currency_id'] : null,
            createdBy: isset($data['created_by']) ? (int) $data['created_by'] : null,
            registrationNumber: $data['registration_number'] ?? null,
            chassisNumber: $data['chassis_number'] ?? null,
            engineNumber: $data['engine_number'] ?? null,
            yearOfManufacture: isset($data['year_of_manufacture']) ? (int) $data['year_of_manufacture'] : null,
            make: $data['make'] ?? null,
            model: $data['model'] ?? null,
            color: $data['color'] ?? null,
            fuelType: $data['fuel_type'] ?? null,
            purchaseCost: $data['purchase_cost'] ?? null,
            bookValue: $data['book_value'] ?? null,
            purchaseDate: isset($data['purchase_date']) ? new \DateTimeImmutable($data['purchase_date']) : null,
            currentOdometer: $data['current_odometer'] ?? null,
            engineHours: $data['engine_hours'] ?? null,
            notes: $data['notes'] ?? null,
            metadata: $data['metadata'] ?? null,
        );

        return DB::transaction(fn (): Asset => $this->assetRepository->save($asset));
    }
}
