<?php declare(strict_types=1);

namespace Modules\Asset\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Asset\Domain\Entities\Asset;
use Modules\Asset\Domain\RepositoryInterfaces\AssetRepositoryInterface;
use Modules\Asset\Infrastructure\Persistence\Eloquent\Models\AssetModel;

class EloquentAssetRepository implements AssetRepositoryInterface
{
    public function create(Asset $asset): void
    {
        AssetModel::create([
            'id' => $asset->getId(),
            'tenant_id' => $asset->getTenantId(),
            'asset_owner_id' => $asset->getAssetOwnerId(),
            'name' => $asset->getName(),
            'type' => $asset->getType(),
            'serial_number' => $asset->getSerialNumber(),
            'purchase_date' => $asset->getPurchaseDate(),
            'acquisition_cost' => $asset->getAcquisitionCost(),
            'status' => $asset->getStatus(),
            'depreciation_method' => $asset->getDepreciationMethod(),
            'useful_life_years' => $asset->getUsefulLifeYears(),
            'salvage_value' => $asset->getSalvageValue(),
        ]);
    }

    public function findById(string $id): ?Asset
    {
        $model = AssetModel::find($id);
        return $model ? $this->toDomain($model) : null;
    }

    public function findBySerialNumber(string $serialNumber): ?Asset
    {
        $model = AssetModel::where('serial_number', $serialNumber)->first();
        return $model ? $this->toDomain($model) : null;
    }

    public function getAllByTenant(
        string $tenantId,
        array $filters = [],
        int $page = 1,
        int $limit = 50,
    ): array {
        $query = AssetModel::byTenant($tenantId);

        if (isset($filters['status'])) {
            $query->byStatus($filters['status']);
        }
        if (isset($filters['type'])) {
            $query->byType($filters['type']);
        }

        $total = $query->count();
        $data = $query->paginate($limit, ['*'], 'page', $page)->items();

        return [
            'data' => array_map(fn($m) => $this->toDomain($m), $data),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    public function getAllByOwner(
        string $tenantId,
        string $assetOwnerId,
        int $page = 1,
        int $limit = 50,
    ): array {
        $query = AssetModel::byTenant($tenantId)->byOwner($assetOwnerId);
        $total = $query->count();
        $data = $query->paginate($limit, ['*'], 'page', $page)->items();

        return [
            'data' => array_map(fn($m) => $this->toDomain($m), $data),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    public function getAllByStatus(
        string $tenantId,
        string $status,
        int $page = 1,
        int $limit = 50,
    ): array {
        $query = AssetModel::byTenant($tenantId)->byStatus($status);
        $total = $query->count();
        $data = $query->paginate($limit, ['*'], 'page', $page)->items();

        return [
            'data' => array_map(fn($m) => $this->toDomain($m), $data),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    public function update(Asset $asset): void
    {
        AssetModel::where('id', $asset->getId())->update([
            'status' => $asset->getStatus(),
        ]);
    }

    public function delete(string $id): void
    {
        AssetModel::where('id', $id)->delete();
    }

    public function countByTenant(string $tenantId): int
    {
        return AssetModel::byTenant($tenantId)->count();
    }

    public function countByStatus(string $tenantId, string $status): int
    {
        return AssetModel::byTenant($tenantId)->byStatus($status)->count();
    }

    private function toDomain(AssetModel $model): Asset
    {
        return new Asset(
            $model->id,
            $model->tenant_id,
            $model->name,
            $model->type,
            $model->serial_number,
            $model->asset_owner_id,
            $model->purchase_date,
            $model->acquisition_cost,
            $model->status,
            $model->depreciation_method,
            $model->useful_life_years,
            $model->salvage_value,
        );
    }
}
