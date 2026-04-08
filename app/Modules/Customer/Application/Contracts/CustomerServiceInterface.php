<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Contracts;

use Modules\Customer\Application\DTOs\CustomerData;

interface CustomerServiceInterface
{
    public function create(CustomerData $dto, int $tenantId): mixed;

    public function update(int $id, CustomerData $dto): mixed;

    public function delete(int $id): bool;

    public function find(mixed $id): mixed;

    public function list(array $filters = [], ?int $perPage = null): mixed;
}
