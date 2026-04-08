<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Contracts;

use Modules\Supplier\Application\DTOs\SupplierData;

interface SupplierServiceInterface
{
    public function create(SupplierData $dto, int $tenantId): mixed;

    public function update(int $id, SupplierData $dto): mixed;

    public function delete(int $id): bool;

    public function find(mixed $id): mixed;

    public function list(array $filters = [], ?int $perPage = null): mixed;
}
