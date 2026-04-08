<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Application\DTOs\BatchLotData;

interface BatchLotServiceInterface
{
    public function create(BatchLotData $dto, int $tenantId): mixed;

    public function update(int $id, BatchLotData $dto): mixed;

    public function find(mixed $id): mixed;

    public function list(array $filters = [], ?int $perPage = null): mixed;

    public function delete(int $id): void;
}
