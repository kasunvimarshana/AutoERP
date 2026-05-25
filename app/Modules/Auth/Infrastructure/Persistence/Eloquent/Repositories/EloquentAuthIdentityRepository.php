<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Auth\Application\Repositories\AuthIdentityRepositoryInterface;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\AuthIdentityModel;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;

final class EloquentAuthIdentityRepository extends EloquentRepository implements AuthIdentityRepositoryInterface
{
    public function __construct(AuthIdentityModel $model)
    {
        parent::__construct($model);
    }

    public function findByProviderAndSubject(?int $tenantId, int $providerId, string $providerUserKey): ?DataRecord
    {
        $query = $this->query()
            ->where('provider_id', $providerId)
            ->where('provider_user_key', strtolower(trim($providerUserKey)));

        if ($tenantId === null) {
            $query->whereNull('tenant_id');
        } else {
            $query->where('tenant_id', $tenantId);
        }

        $model = $query->first();

        return $model === null ? null : $this->toRecord($model);
    }

    public function findByUserAndProvider(?int $tenantId, int $userId, int $providerId): ?DataRecord
    {
        $query = $this->query()
            ->where('user_id', $userId)
            ->where('provider_id', $providerId);

        if ($tenantId === null) {
            $query->whereNull('tenant_id');
        } else {
            $query->where('tenant_id', $tenantId);
        }

        $model = $query->first();

        return $model === null ? null : $this->toRecord($model);
    }
}
