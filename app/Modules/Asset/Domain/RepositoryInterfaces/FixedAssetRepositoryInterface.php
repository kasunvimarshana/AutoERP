<?php
declare(strict_types=1);
namespace Modules\Asset\Domain\RepositoryInterfaces;
use Modules\Asset\Domain\Entities\FixedAsset;
interface FixedAssetRepositoryInterface {
    public function findById(int $id): ?FixedAsset;
    public function findByCode(int $tenantId, string $code): ?FixedAsset;
    public function findAllByTenant(int $tenantId, array $filters = []): array;
    public function create(array $data): FixedAsset;
    public function update(int $id, array $data): ?FixedAsset;
    public function delete(int $id): bool;
}
