<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Auth\Application\Repositories\AuthProviderRepositoryInterface;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\AuthProviderModel;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;

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
