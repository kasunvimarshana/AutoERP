<?php

namespace Modules\Audit\Infrastructure\Repositories;

use Modules\Audit\Domain\Contracts\AuditRepositoryInterface;
use Modules\Audit\Infrastructure\Models\AuditModel;

class AuditRepository implements AuditRepositoryInterface
{
    public function __construct(private AuditModel $model) {}

    public function paginate(array $filters, int $perPage = 50): object
    {
        $query = $this->model->newQuery();

        if (!empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (!empty($filters['model_type'])) {
            $query->where('model_type', $filters['model_type']);
        }

        if (!empty($filters['model_id'])) {
            $query->where('model_id', $filters['model_id']);
        }

        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function findById(string $id): ?object
    {
        return $this->model->newQuery()->find($id);
    }
}
