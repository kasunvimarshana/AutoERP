<?php declare(strict_types=1);

namespace Modules\Driver\Application\Contracts;

use Modules\Driver\Domain\Entities\Driver;

interface ManageDriverServiceInterface
{
    public function create(array $data): Driver;

    public function update(int $tenantId, string $id, array $data): Driver;

    public function find(int $tenantId, string $id): Driver;

    public function delete(int $tenantId, string $id): void;

    public function list(int $tenantId, int $perPage = 15, int $page = 1): array;

    public function getAvailable(int $tenantId): array;
}
