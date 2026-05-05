<?php declare(strict_types=1);

namespace Modules\Asset\Application\Contracts;

use Modules\Asset\Domain\Entities\Asset;

interface ManageAssetServiceInterface
{
    public function create(array $data): Asset;

    public function update(int $tenantId, string $id, array $data): Asset;

    public function find(int $tenantId, string $id): Asset;

    public function delete(int $tenantId, string $id): void;

    public function list(int $tenantId, int $perPage = 15, int $page = 1, array $filters = []): array;

    public function getAllByOwner(int $tenantId, string $ownerId, int $page = 1, int $limit = 50): array;
}
