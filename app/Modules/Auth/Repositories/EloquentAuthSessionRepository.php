<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use Modules\Auth\Models\AuthSessionModel;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\EloquentRepository;

final class EloquentAuthSessionRepository extends EloquentRepository implements AuthSessionRepositoryInterface
{
    public function __construct(AuthSessionModel $model)
    {
        parent::__construct($model);
    }

    /**
     * @return list<DataRecord>
     */
    public function listActiveByUser(?int $tenantId, int $userId): array
    {
        $query = $this->query()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->orderByDesc('last_activity_at');

        if ($tenantId === null) {
            $query->whereNull('tenant_id');
        } else {
            $query->where('tenant_id', $tenantId);
        }

        $records = [];
        foreach ($query->get() as $model) {
            if ($model instanceof AuthSessionModel) {
                $records[] = $this->toRecord($model);
            }
        }

        return $records;
    }
}
