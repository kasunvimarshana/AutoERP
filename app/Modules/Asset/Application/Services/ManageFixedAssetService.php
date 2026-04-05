<?php
declare(strict_types=1);
namespace Modules\Asset\Application\Services;

use Modules\Asset\Domain\Entities\FixedAsset;
use Modules\Asset\Domain\Exceptions\FixedAssetNotFoundException;
use Modules\Asset\Domain\RepositoryInterfaces\FixedAssetRepositoryInterface;

class ManageFixedAssetService
{
    public function __construct(private readonly FixedAssetRepositoryInterface $repository) {}

    public function findById(int $id): FixedAsset
    {
        $asset = $this->repository->findById($id);
        if ($asset === null) throw new FixedAssetNotFoundException($id);
        return $asset;
    }

    public function findAllByTenant(int $tenantId, array $filters = []): array
    {
        return $this->repository->findAllByTenant($tenantId, $filters);
    }

    public function create(array $data): FixedAsset
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): FixedAsset
    {
        $this->findById($id);
        return $this->repository->update($id, $data) ?? $this->findById($id);
    }

    public function dispose(int $id, \DateTimeInterface $date): FixedAsset
    {
        $asset = $this->findById($id);
        $asset->dispose($date);
        return $this->repository->update($id, [
            'status'       => FixedAsset::STATUS_DISPOSED,
            'disposal_date' => $date,
        ]) ?? $asset;
    }

    public function sell(int $id, \DateTimeInterface $date): FixedAsset
    {
        $asset = $this->findById($id);
        $asset->sell($date);
        return $this->repository->update($id, [
            'status'       => FixedAsset::STATUS_SOLD,
            'disposal_date' => $date,
        ]) ?? $asset;
    }

    public function delete(int $id): void
    {
        $this->findById($id);
        $this->repository->delete($id);
    }
}
