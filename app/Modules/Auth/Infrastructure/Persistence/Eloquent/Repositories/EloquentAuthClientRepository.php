<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Auth\Application\Repositories\AuthClientRepositoryInterface;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\AuthClientModel;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;

final class EloquentAuthClientRepository extends EloquentRepository implements AuthClientRepositoryInterface
{
    public function __construct(AuthClientModel $model)
    {
        parent::__construct($model);
    }

    public function findByClientKey(?int $tenantId, string $clientKey): ?DataRecord
    {
        $query = $this->query()->where('client_key', trim($clientKey));

        if ($tenantId === null) {
            $query->whereNull('tenant_id');
        } else {
            $query->where('tenant_id', $tenantId);
        }

        $model = $query->first();

        return $model === null ? null : $this->toRecord($model);
    }
}
