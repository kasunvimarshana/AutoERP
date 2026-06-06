<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use Modules\Auth\Models\AuthClientModel;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\EloquentRepository;

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
