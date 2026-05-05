<?php declare(strict_types=1);

namespace Modules\Asset\Application\Services;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Asset\Application\Contracts\ManageAssetServiceInterface;
use Modules\Asset\Domain\Entities\Asset;
use Modules\Asset\Domain\RepositoryInterfaces\AssetRepositoryInterface;

class ManageAssetService implements ManageAssetServiceInterface
{
    public function __construct(
        private readonly AssetRepositoryInterface $assets,
    ) {}

    public function create(array $data): Asset
    {
        return DB::transaction(function () use ($data): Asset {
            $asset = new Asset(
                id: Str::uuid()->toString(),
                tenantId: (string) $data['tenant_id'],
                name: $data['name'],
                type: $data['type'],
                serialNumber: $data['serial_number'] ?? null,
                assetOwnerId: $data['asset_owner_id'],
                purchaseDate: new \DateTime($data['purchase_date']),
                acquisitionCost: (string) $data['acquisition_cost'],
                status: $data['status'] ?? 'active',
                depreciationMethod: $data['depreciation_method'] ?? 'straight_line',
                usefulLifeYears: (int) $data['useful_life_years'],
                salvageValue: (string) $data['salvage_value'],
            );

            $this->assets->create($asset);
            return $asset;
        });
    }

    public function update(int $tenantId, string $id, array $data): Asset
    {
        return DB::transaction(function () use ($tenantId, $id, $data): Asset {
            $asset = $this->assets->findById($id);
            if (!$asset || $asset->getTenantId() !== (string) $tenantId) {
                throw new \Exception('Asset not found');
            }

            if (isset($data['status'])) {
                $asset->updateStatus($data['status']);
            }

            $this->assets->update($asset);
            return $this->assets->findById($id);
        });
    }

    public function find(int $tenantId, string $id): Asset
    {
        $asset = $this->assets->findById($id);
        if (!$asset || $asset->getTenantId() !== (string) $tenantId) {
            throw new \Exception('Asset not found');
        }
        return $asset;
    }

    public function delete(int $tenantId, string $id): void
    {
        $asset = $this->find($tenantId, $id);
        $this->assets->delete($id);
    }

    public function list(int $tenantId, int $perPage = 15, int $page = 1, array $filters = []): array
    {
        return $this->assets->getAllByTenant((string) $tenantId, $filters, $page, $perPage);
    }

    public function getAllByOwner(int $tenantId, string $ownerId, int $page = 1, int $limit = 50): array
    {
        return $this->assets->getAllByOwner((string) $tenantId, $ownerId, $page, $limit);
    }
}
