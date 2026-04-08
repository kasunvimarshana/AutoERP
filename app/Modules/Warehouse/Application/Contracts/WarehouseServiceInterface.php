<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Contracts;

use Illuminate\Support\Collection;
use Modules\Warehouse\Application\DTOs\WarehouseData;

interface WarehouseServiceInterface
{
    public function create(WarehouseData $dto, int $tenantId): mixed;

    public function update(int $id, WarehouseData $dto): mixed;

    public function delete(int $id): bool;

    public function find(mixed $id): mixed;

    public function list(array $filters = [], ?int $perPage = null): mixed;
}
