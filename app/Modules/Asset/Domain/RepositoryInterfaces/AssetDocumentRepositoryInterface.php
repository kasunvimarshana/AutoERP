<?php declare(strict_types=1);

namespace Modules\Asset\Domain\RepositoryInterfaces;

use Modules\Asset\Domain\Entities\AssetDocument;

interface AssetDocumentRepositoryInterface
{
    public function create(AssetDocument $document): void;
    public function findById(string $id): ?AssetDocument;
    public function getByAsset(string $assetId, int $page = 1, int $limit = 50): array;
    public function getByType(string $tenantId, string $type, int $page = 1, int $limit = 50): array;
    public function getExpiringDocuments(string $tenantId, int $daysThreshold = 30): array;
    public function getExpiredDocuments(string $tenantId): array;
    public function update(AssetDocument $document): void;
    public function delete(string $id): void;
}
