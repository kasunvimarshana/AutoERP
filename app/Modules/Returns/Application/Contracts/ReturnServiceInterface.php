<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Contracts;

use Modules\Returns\Application\DTOs\ReturnData;

interface ReturnServiceInterface
{
    public function create(ReturnData $dto, int $tenantId): mixed;

    public function update(int $id, ReturnData $dto): mixed;

    public function approve(int $id, int $userId, int $tenantId): mixed;

    public function process(int $id, int $userId, int $tenantId): mixed;

    public function reject(int $id, string $reason, int $tenantId): mixed;

    public function delete(int $id): bool;

    public function find(mixed $id): mixed;

    public function list(array $filters = [], ?int $perPage = null): mixed;
}
