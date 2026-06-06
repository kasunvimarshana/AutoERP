<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use Modules\Auth\Models\AuthProviderModel;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\EloquentRepository;

final class EloquentAuthProviderRepository extends EloquentRepository implements AuthProviderRepositoryInterface
{
    public function __construct(AuthProviderModel $model)
    {
        parent::__construct($model);
    }

    public function findActiveByKey(?int $tenantId, string $providerKey): ?DataRecord
    {
        $query = $this->query()
            ->where('provider_key', strtolower(trim($providerKey)))
            ->where('status', 'active');

        if ($tenantId === null) {
            $query->whereNull('tenant_id');
        } else {
            $query->where('tenant_id', $tenantId);
        }

        $model = $query->first();

        return $model === null ? null : $this->toRecord($model);
    }
}
