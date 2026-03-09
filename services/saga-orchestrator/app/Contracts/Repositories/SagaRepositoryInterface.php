<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Domain\Saga\Models\SagaTransaction;

/**
 * Saga Repository Interface
 */
interface SagaRepositoryInterface
{
    public function create(array $data): SagaTransaction;
    public function findById(string $id): ?SagaTransaction;
    public function updateStatus(string $sagaId, string $status, ?array $result = null, ?string $failureReason = null): bool;
    public function getByTenant(string $tenantId, array $params = []): mixed;
}
