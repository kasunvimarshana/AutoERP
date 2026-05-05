<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Rental\Domain\Entities\Asset;
use Modules\Rental\Domain\RepositoryInterfaces\AssetRepositoryInterface;
use Modules\Rental\Infrastructure\Persistence\Eloquent\Models\AssetModel;

class EloquentAssetRepository implements AssetRepositoryInterface
{
    public function __construct(
        private readonly AssetModel $model,
    ) {}

    public function save(Asset $asset): Asset
    {
        if ($asset->getId() !== null) {
            /** @var AssetModel $record */
            $record = $this->model->newQuery()->findOrFail($asset->getId());
            $record->update($this->toArray($asset));
            $record->refresh();
        } else {
            /** @var AssetModel $record */
            $record = $this->model->newQuery()->create($this->toArray($asset));
        }

        return $this->mapToEntity($record);
    }

    public function findById(int $tenantId, int $id): ?Asset
    {
        /** @var AssetModel|null $record */
        $record = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $record !== null ? $this->mapToEntity($record) : null;
    }

    public function findByCode(int $tenantId, string $assetCode): ?Asset
    {
        /** @var AssetModel|null $record */
        $record = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('asset_code', $assetCode)
            ->first();

        return $record !== null ? $this->mapToEntity($record) : null;
    }

    public function paginate(int $tenantId, array $filters, int $perPage, int $page): array
    {
        $query = $this->model->newQuery()->where('tenant_id', $tenantId);

        foreach ($filters as $field => $value) {
            if ($value !== null && $value !== '') {
                $query->where($field, $value);
            }
        }

        $paginator = $query->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => array_map(fn (AssetModel $m): Asset => $this->mapToEntity($m), $paginator->items()),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
        ];
    }

    public function existsByCode(int $tenantId, string $assetCode, ?int $excludeId = null): bool
    {
        $query = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('asset_code', $assetCode);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    private function toArray(Asset $asset): array
    {
        return [
            'tenant_id' => $asset->getTenantId(),
            'org_unit_id' => $asset->getOrgUnitId(),
            'row_version' => $asset->getRowVersion(),
            'asset_code' => $asset->getAssetCode(),
            'asset_name' => $asset->getAssetName(),
            'usage_mode' => $asset->getUsageMode(),
            'lifecycle_status' => $asset->getLifecycleStatus(),
            'rental_status' => $asset->getRentalStatus(),
            'service_status' => $asset->getServiceStatus(),
            'product_id' => $asset->getProductId(),
            'serial_id' => $asset->getSerialId(),
            'supplier_id' => $asset->getSupplierId(),
            'warehouse_id' => $asset->getWarehouseId(),
            'currency_id' => $asset->getCurrencyId(),
            'created_by' => $asset->getCreatedBy(),
            'registration_number' => $asset->getRegistrationNumber(),
            'chassis_number' => $asset->getChassisNumber(),
            'engine_number' => $asset->getEngineNumber(),
            'year_of_manufacture' => $asset->getYearOfManufacture(),
            'make' => $asset->getMake(),
            'model' => $asset->getModel(),
            'color' => $asset->getColor(),
            'fuel_type' => $asset->getFuelType(),
            'purchase_cost' => $asset->getPurchaseCost(),
            'book_value' => $asset->getBookValue(),
            'purchase_date' => $asset->getPurchaseDate()?->format('Y-m-d'),
            'current_odometer' => $asset->getCurrentOdometer(),
            'engine_hours' => $asset->getEngineHours(),
            'notes' => $asset->getNotes(),
            'metadata' => $asset->getMetadata(),
        ];
    }

    private function mapToEntity(AssetModel $model): Asset
    {
        return new Asset(
            tenantId: (int) $model->tenant_id,
            assetCode: (string) $model->asset_code,
            assetName: (string) $model->asset_name,
            usageMode: (string) $model->usage_mode,
            lifecycleStatus: (string) $model->lifecycle_status,
            rentalStatus: (string) $model->rental_status,
            serviceStatus: (string) $model->service_status,
            orgUnitId: $model->org_unit_id !== null ? (int) $model->org_unit_id : null,
            productId: $model->product_id !== null ? (int) $model->product_id : null,
            serialId: $model->serial_id !== null ? (int) $model->serial_id : null,
            supplierId: $model->supplier_id !== null ? (int) $model->supplier_id : null,
            warehouseId: $model->warehouse_id !== null ? (int) $model->warehouse_id : null,
            currencyId: $model->currency_id !== null ? (int) $model->currency_id : null,
            createdBy: $model->created_by !== null ? (int) $model->created_by : null,
            registrationNumber: $model->registration_number,
            chassisNumber: $model->chassis_number,
            engineNumber: $model->engine_number,
            yearOfManufacture: $model->year_of_manufacture !== null ? (int) $model->year_of_manufacture : null,
            make: $model->make,
            model: $model->model,
            color: $model->color,
            fuelType: $model->fuel_type,
            purchaseCost: $model->purchase_cost !== null ? (string) $model->purchase_cost : null,
            bookValue: $model->book_value !== null ? (string) $model->book_value : null,
            purchaseDate: $model->purchase_date,
            currentOdometer: $model->current_odometer !== null ? (string) $model->current_odometer : null,
            engineHours: $model->engine_hours !== null ? (string) $model->engine_hours : null,
            notes: $model->notes,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            rowVersion: (int) $model->row_version,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
            id: (int) $model->id,
        );
    }
}
