<?php

declare(strict_types=1);

namespace Modules\User\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\EloquentRepository;
use Modules\User\Models\UserDocumentModel;

final class EloquentUserDocumentRepository extends EloquentRepository implements UserDocumentRepositoryInterface
{
    public function __construct(UserDocumentModel $model)
    {
        parent::__construct($model);
    }

    public function findByTenantUserName(?int $tenantId, int $userId, string $name, ?int $excludeId = null): ?DataRecord
    {
        $query = $this->query()
            ->where('user_id', $userId)
            ->where('name', trim($name));

        $this->applyTenantScope($query, $tenantId);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $model = $query->first();

        return $model instanceof Model ? $this->toRecord($model) : null;
    }

    private function applyTenantScope(Builder $query, ?int $tenantId): void
    {
        if ($tenantId === null) {
            $query->whereNull('tenant_id');

            return;
        }

        $query->where('tenant_id', $tenantId);
    }
}
