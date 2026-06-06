<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\EloquentRepository;
use Modules\OrganizationUnit\Models\OrganizationUnitDocumentModel;

final class EloquentOrganizationUnitDocumentRepository extends EloquentRepository implements OrganizationUnitDocumentRepositoryInterface
{
    public function __construct(OrganizationUnitDocumentModel $model)
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

    public function findByTenantAndOrganizationUnitAndName(
        int|string $tenantId,
        int|string $organizationUnitId,
        string $name,
    ): ?DataRecord {
        $model = $this->query()
            ->where('tenant_id', $tenantId)
            ->where('organization_unit_id', $organizationUnitId)
            ->where('name', trim($name))
            ->first();

        if (! $model instanceof Model) {
            return null;
        }

        return $this->toRecord($model);
    }
}
