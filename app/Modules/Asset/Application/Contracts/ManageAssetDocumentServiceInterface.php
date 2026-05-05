<?php declare(strict_types=1);

namespace Modules\Asset\Application\Contracts;

use Modules\Asset\Domain\Entities\AssetDocument;

interface ManageAssetDocumentServiceInterface
{
    public function create(array $data): AssetDocument;

    public function update(int $tenantId, string $id, array $data): AssetDocument;

    public function find(int $tenantId, string $id): AssetDocument;

    public function delete(int $tenantId, string $id): void;

    public function list(int $tenantId, int $perPage = 15, int $page = 1): array;

    public function getExpiring(int $tenantId, int $daysThreshold = 30): array;
}
