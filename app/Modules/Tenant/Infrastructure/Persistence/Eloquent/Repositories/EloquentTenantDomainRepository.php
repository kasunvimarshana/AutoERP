<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Tenant\Application\Repositories\TenantDomainRepositoryInterface;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantDomainModel;

final class EloquentTenantDomainRepository extends EloquentRepository implements TenantDomainRepositoryInterface
{
    public function __construct(TenantDomainModel $model)
    {
        parent::__construct($model);
    }

    public function listByTenant(int|string $tenantId): array
    {
        $records = [];

        foreach (
            $this->query()
                ->where('tenant_id', $tenantId)
                ->orderByDesc('is_primary')
                ->orderBy('domain')
                ->get() as $model
        ) {
            if ($model instanceof Model) {
                $records[] = $this->toRecord($model);
            }
        }

        return $records;
    }

    public function findByDomain(string $domain): ?DataRecord
    {
        $model = $this->query()->where('domain', strtolower(trim($domain)))->first();

        if (! $model instanceof Model) {
            return null;
        }

        return $this->toRecord($model);
    }

    public function findPrimaryByTenant(int|string $tenantId): ?DataRecord
    {
        $model = $this->query()->where('tenant_id', $tenantId)->where('is_primary', true)->first();

        if (! $model instanceof Model) {
            return null;
        }

        return $this->toRecord($model);
    }

    public function clearPrimaryForTenant(int|string $tenantId): int
    {
        return $this->query()
            ->where('tenant_id', $tenantId)
            ->where('is_primary', true)
            ->update([
                'is_primary' => false,
                'row_version' => \Illuminate\Support\Facades\DB::raw('row_version + 1'),
                'updated_at' => now(),
            ]);
    }
}
