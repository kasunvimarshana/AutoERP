<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Application\DTOs\SerialNumberData;

interface SerialNumberServiceInterface
{
    public function create(SerialNumberData $dto, int $tenantId): mixed;

    public function update(int $id, SerialNumberData $dto): mixed;

    public function find(mixed $id): mixed;

    public function findBySerial(string $serial, int $productId): mixed;

    public function list(array $filters = [], ?int $perPage = null): mixed;

    public function delete(int $id): void;
}
