<?php

declare(strict_types=1);

namespace Modules\User\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\OrganizationUnitDirectoryInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;
use Modules\Core\Results\Result;
use Modules\User\Constants\UserOrganizationUnitStatus;
use Modules\User\Constants\UserPermission;
use Modules\User\Models\UserModel;
use Throwable;

final class UserReadService extends AbstractUserCrudService
{
    public function __construct(
        private readonly CurrentTenantContextAccessorInterface $tenant,
        private readonly CurrentUserContextAccessorInterface $actor,
        private readonly UserAuthorizationService $authorization,
        private readonly OrganizationUnitDirectoryInterface $organizationUnits,
    ) {}

    public function list(array $filters): Result
    {
        try {
            $this->requireViewPermission();
            $tenantId = $this->requireTenantId();
            $query = $this->baseQuery()->where('tenant_id', $tenantId);
            $status = trim((string) ($filters['status'] ?? ''));
            if ($status !== '') {
                $query->where('status', strtolower($status));
            }
            $roleId = $this->toNullableInt($filters['role_id'] ?? null);
            if ($roleId !== null) {
                $query->whereHas('roles', fn (Builder $roles): Builder => $roles->where('role_id', $roleId));
            }
            $organizationUnitId = $this->toNullableInt($filters['organization_unit_filter_id'] ?? null);
            if ($organizationUnitId !== null) {
                $query->whereHas('organizationUnitAssignments', fn (Builder $assignments): Builder => $assignments
                    ->where('organization_unit_id', $organizationUnitId)
                    ->where('status', UserOrganizationUnitStatus::ACTIVE));
            }
            $search = trim((string) ($filters['search'] ?? ''));
            if ($search !== '') {
                $prefix = $search.'%';
                $query->where(function (Builder $builder) use ($prefix): void {
                    $builder->where('first_name', 'like', $prefix)
                        ->orWhere('last_name', 'like', $prefix)
                        ->orWhere('username', 'like', $prefix)
                        ->orWhere('email', 'like', $prefix)
                        ->orWhere('phone', 'like', $prefix);
                });
            }
            $perPage = min(max((int) ($filters['per_page'] ?? 25), 1), 100);
            $page = max((int) ($filters['page'] ?? 1), 1);
            $paginator = $query->orderBy('first_name')->orderBy('last_name')
                ->paginate($perPage, ['*'], 'page', $page);
            $models = array_values($paginator->items());
            $unitMap = $this->organizationUnitMap($tenantId, $models);
            $items = array_map(
                fn (UserModel $model): DataRecord => $this->record($model, $unitMap),
                $models,
            );

            return Result::success(new PagedResult(
                $items,
                $paginator->total(),
                $paginator->currentPage(),
                $paginator->perPage(),
            ));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function get(int|string $id): Result
    {
        try {
            $tenantId = $this->requireTenantId();
            $model = $this->baseQuery()->where('tenant_id', $tenantId)->whereKey($id)->first();
            if (! $model instanceof UserModel) {
                return $this->notFound('User not found.');
            }
            if ((int) $model->getKey() !== $this->actor->currentUserId()
                && ! $this->authorization->canCurrent(UserPermission::USERS_VIEW)) {
                throw new AuthorizationException('User profile access is not authorized.');
            }

            return Result::success($this->record(
                $model,
                $this->organizationUnitMap($tenantId, [$model]),
            ));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    private function baseQuery(): Builder
    {
        return UserModel::query()->with([
            'roles.role:id,tenant_id,name,description,system_key',
            'permissions.permission:id,tenant_id,name,module,description',
            'organizationUnitAssignments:id,tenant_id,user_id,organization_unit_id,status,is_default',
        ]);
    }

    /** @param array<int,array{id:int,code:string,name:string,path:string}> $unitMap */
    private function record(UserModel $user, array $unitMap): DataRecord
    {
        $roles = $user->roles->map(static fn ($assignment): ?array => $assignment->role === null ? null : [
            'id' => (int) $assignment->role->getKey(),
            'name' => (string) $assignment->role->getAttribute('name'),
            'description' => $assignment->role->getAttribute('description'),
            'system_key' => $assignment->role->getAttribute('system_key'),
        ])->filter()->values()->all();
        $directPermissions = $user->permissions->map(static fn ($assignment): ?array => $assignment->permission === null ? null : [
            'id' => (int) $assignment->permission->getKey(),
            'name' => (string) $assignment->permission->getAttribute('name'),
            'module' => (string) $assignment->permission->getAttribute('module'),
            'description' => $assignment->permission->getAttribute('description'),
        ])->filter()->values()->all();

        $organizationUnits = $user->organizationUnitAssignments
            ->filter(static fn ($assignment): bool => (string) $assignment->getAttribute('status') === UserOrganizationUnitStatus::ACTIVE)
            ->map(static function ($assignment) use ($unitMap): ?array {
                $id = (int) $assignment->getAttribute('organization_unit_id');
                $summary = $unitMap[$id] ?? null;
                if ($summary === null) {
                    return null;
                }

                return $summary + ['is_default' => (bool) $assignment->getAttribute('is_default')];
            })->filter()->values()->all();

        $defaultOrganizationUnit = collect($organizationUnits)->firstWhere('is_default', true);

        return new DataRecord([
            'id' => (int) $user->getKey(),
            'row_version' => (int) $user->getAttribute('row_version'),
            'first_name' => (string) $user->getAttribute('first_name'),
            'last_name' => $user->getAttribute('last_name'),
            'name' => trim((string) $user->getAttribute('first_name').' '.(string) $user->getAttribute('last_name')),
            'username' => $user->getAttribute('username'),
            'email' => (string) $user->getAttribute('email'),
            'phone' => $user->getAttribute('phone'),
            'status' => (string) $user->getAttribute('status'),
            'credentials_ready' => $user->getAttribute('credentials_ready_at') !== null,
            'invited_at' => $user->getAttribute('invited_at')?->toAtomString(),
            'activated_at' => $user->getAttribute('activated_at')?->toAtomString(),
            'roles' => $roles,
            'direct_permissions' => $directPermissions,
            'organization_units' => $organizationUnits,
            'default_organization_unit_id' => is_array($defaultOrganizationUnit)
                ? ($defaultOrganizationUnit['id'] ?? null)
                : null,
            'created_at' => $user->getAttribute('created_at')?->toAtomString(),
            'updated_at' => $user->getAttribute('updated_at')?->toAtomString(),
        ]);
    }

    /** @param list<UserModel> $users @return array<int,array{id:int,code:string,name:string,path:string}> */
    private function organizationUnitMap(int $tenantId, array $users): array
    {
        $ids = [];
        foreach ($users as $user) {
            foreach ($user->organizationUnitAssignments as $assignment) {
                if ((string) $assignment->getAttribute('status') === UserOrganizationUnitStatus::ACTIVE) {
                    $id = (int) $assignment->getAttribute('organization_unit_id');
                    if ($id > 0) {
                        $ids[$id] = $id;
                    }
                }
            }
        }

        return $this->organizationUnits->summaries($tenantId, array_values($ids));
    }

    private function requireViewPermission(): void
    {
        if (! $this->authorization->canCurrent(UserPermission::USERS_VIEW)) {
            throw new AuthorizationException('User list access is not authorized.');
        }
    }

    private function requireTenantId(): int
    {
        $tenantId = $this->tenant->currentTenantId();
        if ($tenantId === null) {
            throw new \RuntimeException('A tenant context is required.');
        }

        return $tenantId;
    }
}
