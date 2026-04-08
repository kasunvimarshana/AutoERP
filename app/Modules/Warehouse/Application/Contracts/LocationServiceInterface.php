<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Contracts;

use Illuminate\Support\Collection;
use Modules\Warehouse\Application\DTOs\LocationData;

interface LocationServiceInterface
{
    public function create(LocationData $dto, int $tenantId): mixed;

    public function update(int $id, LocationData $dto): mixed;

    public function delete(int $id): bool;

    public function find(mixed $id): mixed;

    public function list(array $filters = [], ?int $perPage = null): mixed;

    public function getTree(int $warehouseId): Collection;
}
