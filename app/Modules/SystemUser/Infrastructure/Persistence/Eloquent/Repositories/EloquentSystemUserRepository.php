<?php

declare(strict_types=1);

namespace Modules\SystemUser\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\SystemUser\Application\Repositories\SystemUserRepositoryInterface;
use Modules\SystemUser\Infrastructure\Persistence\Eloquent\Models\SystemUserModel;

final class EloquentSystemUserRepository extends EloquentRepository implements SystemUserRepositoryInterface
{
    public function __construct(SystemUserModel $model)
    {
        parent::__construct($model);
    }

    public function findByTenantAndUserId(int $tenantId, int $userId): ?DataRecord
    {
        $model = $this->query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->first();

        if (! $model instanceof Model) {
            return null;
        }

        return $this->toRecord($model);
    }

    public function pageByFilters(
        ?int $tenantId,
        ?int $organizationUnitId,
        ?int $userId,
        ?string $status,
        ?string $search,
        int $perPage,
        int $page,
    ): PagedResult {
        $query = $this->query();

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        if ($organizationUnitId !== null) {
            $query->where('organization_unit_id', $organizationUnitId);
        }

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        if ($status !== null && trim($status) !== '') {
            $query->where('status', trim($status));
        }

        if ($search !== null && trim($search) !== '') {
            $searchTerm = trim($search);
            $query->where(function ($nestedQuery) use ($searchTerm): void {
                $nestedQuery->where('code', 'like', '%' . $searchTerm . '%')
                    ->orWhere('registration_number', 'like', '%' . $searchTerm . '%')
                    ->orWhere('notes', 'like', '%' . $searchTerm . '%');
            });
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $items = [];
        foreach ($paginator->items() as $model) {
            if ($model instanceof Model) {
                $items[] = $this->toRecord($model);
            }
        }

        return new PagedResult($items, $paginator->total(), $paginator->currentPage(), $paginator->perPage());
    }
}
