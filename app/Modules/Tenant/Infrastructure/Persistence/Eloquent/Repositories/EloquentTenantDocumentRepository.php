<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Tenant\Application\Repositories\TenantDocumentRepositoryInterface;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantDocumentModel;

final class EloquentTenantDocumentRepository extends EloquentRepository implements TenantDocumentRepositoryInterface
{
    public function __construct(TenantDocumentModel $model)
    {
        parent::__construct($model);
    }

    public function listByTenant(int|string $tenantId): array
    {
        $records = [];

        foreach ($this->query()->where('tenant_id', $tenantId)->orderBy('name')->get() as $model) {
            if ($model instanceof Model) {
                $records[] = $this->toRecord($model);
            }
        }

        return $records;
    }

    public function findByTenantAndName(int|string $tenantId, string $name): ?DataRecord
    {
        $model = $this->query()
            ->where('tenant_id', $tenantId)
            ->where('name', trim($name))
            ->first();

        if (! $model instanceof Model) {
            return null;
        }

        return $this->toRecord($model);
    }
}
