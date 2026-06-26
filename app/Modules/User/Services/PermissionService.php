<?php

declare(strict_types=1);

namespace Modules\User\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;
use Modules\Core\Results\Result;
use Modules\User\Constants\UserGuard;
use Modules\User\Constants\UserPermission;
use Modules\User\Models\PermissionModel;
use RuntimeException;
use Throwable;

final class PermissionService extends AbstractUserCrudService
{
    public function __construct(
        private readonly CurrentTenantContextAccessorInterface $tenant,
        private readonly UserAuthorizationService $authorization,
    ) {}

    public function list(array $filters): Result
    {
        try {
            $this->authorize();
            $query = PermissionModel::query()->where('tenant_id', $this->tenantId())
                ->where('guard_name', UserGuard::TENANT_API)->where('is_active', true);
            $module = trim((string) ($filters['module'] ?? ''));
            if ($module !== '') {
                $query->where('module', $module);
            }
            $search = trim((string) ($filters['search'] ?? ''));
            if ($search !== '') {
                $query->where('name', 'like', $search.'%');
            }
            $perPage = min(max((int) ($filters['per_page'] ?? 50), 1), 100);
            $page = max((int) ($filters['page'] ?? 1), 1);
            $paginator = $query->orderBy('module')->orderBy('name')->paginate($perPage, ['*'], 'page', $page);
            $items = array_map(fn (mixed $permission): DataRecord => $this->record($permission), array_values($paginator->items()));
            return Result::success(new PagedResult($items, $paginator->total(), $paginator->currentPage(), $paginator->perPage()));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function get(int|string $id): Result
    {
        try {
            $this->authorize();
            $permission = PermissionModel::query()->where('tenant_id', $this->tenantId())
                ->where('guard_name', UserGuard::TENANT_API)->whereKey($id)->first();
            return $permission instanceof PermissionModel ? Result::success($this->record($permission)) : $this->notFound('Permission not found.');
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    /** @return Result list<string> */
    public function modules(): Result
    {
        try {
            $this->authorize();
            return Result::success(PermissionModel::query()->where('tenant_id', $this->tenantId())
                ->where('guard_name', UserGuard::TENANT_API)->where('is_active', true)
                ->distinct()->orderBy('module')->pluck('module')->map(static fn ($module): string => (string) $module)->all());
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    private function record(PermissionModel $permission): DataRecord
    {
        return new DataRecord([
            'id' => (int) $permission->getKey(),
            'name' => (string) $permission->getAttribute('name'),
            'module' => (string) $permission->getAttribute('module'),
            'description' => $permission->getAttribute('description'),
            'is_read_only' => true,
        ]);
    }

    private function authorize(): void
    {
        if (! $this->authorization->canCurrent(UserPermission::PERMISSIONS_VIEW)) {
            throw new AuthorizationException('Permission catalogue access is not authorized.');
        }
    }

    private function tenantId(): int
    {
        $id = $this->tenant->currentTenantId();
        if ($id === null) {
            throw new RuntimeException('A tenant context is required.');
        }
        return $id;
    }
}
