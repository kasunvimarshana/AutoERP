<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Contracts\Repositories\SagaRepositoryInterface;
use App\Domain\Saga\Models\SagaTransaction;

/**
 * Saga Repository - persists saga state for durability and recovery.
 */
class SagaRepository implements SagaRepositoryInterface
{
    public function create(array $data): SagaTransaction
    {
        return SagaTransaction::create($data);
    }

    public function findById(string $id): ?SagaTransaction
    {
        return SagaTransaction::with('steps')->find($id);
    }

    public function updateStatus(
        string $sagaId,
        string $status,
        ?array $result = null,
        ?string $failureReason = null
    ): bool {
        $update = ['status' => $status];

        if ($result !== null) {
            $update['result'] = $result;
        }
        if ($failureReason !== null) {
            $update['failure_reason'] = $failureReason;
        }
        if (in_array($status, [SagaTransaction::STATUS_COMPLETED, SagaTransaction::STATUS_COMPENSATED, SagaTransaction::STATUS_FAILED], true)) {
            $update['completed_at'] = now();
        }

        return (bool) SagaTransaction::where('id', $sagaId)->update($update);
    }

    public function getByTenant(string $tenantId, array $params = []): mixed
    {
        $query = SagaTransaction::where('tenant_id', $tenantId)
            ->with('steps')
            ->orderBy('created_at', 'desc');

        if (isset($params['per_page'])) {
            return $query->paginate((int) $params['per_page'], ['*'], 'page', (int) ($params['page'] ?? 1));
        }

        return $query->get();
    }
}
