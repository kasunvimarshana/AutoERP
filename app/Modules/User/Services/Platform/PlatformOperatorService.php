<?php

declare(strict_types=1);

namespace Modules\User\Services\Platform;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Modules\Audit\Constants\AuditEventCategory;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\PasswordHasherInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\User\Constants\PlatformPermission;
use Modules\User\Contracts\PlatformOperatorSessionRevokerInterface;
use Modules\User\Models\PlatformOperatorPermissionModel;
use Modules\User\Models\PlatformPermissionModel;
use Modules\User\Models\UserModel;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class PlatformOperatorService
{
    public function __construct(
        private readonly UserModel $users,
        private readonly PlatformPermissionModel $permissions,
        private readonly PlatformOperatorPermissionModel $assignments,
        private readonly PlatformPermissionCatalogSynchronizer $catalogue,
        private readonly PlatformOperatorSessionRevokerInterface $sessions,
        private readonly PasswordHasherInterface $passwords,
        private readonly ClockInterface $clock,
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly TenantExecutionContextInterface $executionContext,
        private readonly DatabaseManager $database,
        private readonly AuditRecorderInterface $audit,
    ) {}

    public function page(?string $search, ?string $status, int $page, int $perPage): LengthAwarePaginator
    {
        return $this->executionContext->runAsControlPlane(function () use ($search, $status, $page, $perPage): LengthAwarePaginator {
            $query = $this->operatorQuery()
                ->when($status !== null && trim($status) !== '', fn (Builder $builder) => $builder->where('status', trim($status)))
                ->when($search !== null && trim($search) !== '', function (Builder $builder) use ($search): void {
                    $term = trim($search);
                    $builder->where(function (Builder $nested) use ($term): void {
                        $nested
                            ->where('platform_login_email', 'like', "%{$term}%")
                            ->orWhere('first_name', 'like', "%{$term}%")
                            ->orWhere('last_name', 'like', "%{$term}%");
                    });
                })
                ->orderBy('first_name')
                ->orderBy('platform_login_email');

            return $query->paginate($perPage, ['*'], 'page', $page);
        });
    }

    public function find(int $operatorId, bool $lockForUpdate = false): UserModel
    {
        return $this->executionContext->runAsControlPlane(function () use ($operatorId, $lockForUpdate): UserModel {
            $query = $this->operatorQuery()->whereKey($operatorId);
            if ($lockForUpdate) {
                $query->lockForUpdate();
            }

            return $query->first()
                ?? throw (new ModelNotFoundException())->setModel(UserModel::class, [$operatorId]);
        });
    }

    /** @param array<string, mixed> $payload */
    public function create(array $payload): UserModel
    {
        return $this->executionContext->runAsControlPlane(function () use ($payload): UserModel {
            $email = strtolower(trim((string) ($payload['email'] ?? '')));
            $permissionNames = $this->normalizePermissionNames($payload['permissions'] ?? []);
            $this->assertKnownPermissions($permissionNames);

            return $this->database->transaction(function () use ($payload, $email, $permissionNames): UserModel {
                $this->catalogue->synchronize();
                if ($this->users->newQuery()->where('platform_login_email', $email)->exists()) {
                    throw new ConflictHttpException('A platform operator with this email already exists.');
                }

                $operator = new UserModel();
                $operator->forceFill([
                    'tenant_id' => null,
                    'platform_login_email' => $email,
                    'email' => $email,
                    'username' => null,
                    'first_name' => trim((string) ($payload['first_name'] ?? '')),
                    'last_name' => $this->nullableString($payload['last_name'] ?? null),
                    'email_verified_at' => $this->clock->now(),
                    'password' => $this->passwords->hash((string) ($payload['password'] ?? '')),
                    'status' => 'active',
                    'is_platform_operator' => true,
                    'row_version' => 1,
                ]);
                $operator->save();
                $this->syncPermissions($operator, $permissionNames);
                $this->recordAudit('created', $operator, null, $this->snapshot($operator));

                return $this->reload($operator);
            }, 3);
        });
    }

    /** @param list<string> $permissionNames */
    public function synchronizePermissions(int $operatorId, int $expectedVersion, array $permissionNames): UserModel
    {
        return $this->executionContext->runAsControlPlane(function () use ($operatorId, $expectedVersion, $permissionNames): UserModel {
            $permissionNames = $this->normalizePermissionNames($permissionNames);
            $this->assertKnownPermissions($permissionNames);

            return $this->database->transaction(function () use ($operatorId, $expectedVersion, $permissionNames): UserModel {
                $this->catalogue->synchronize();
                $operator = $this->find($operatorId, true);
                $this->assertVersion($operator, $expectedVersion);
                $before = $this->snapshot($operator);
                $currentOperatorId = $this->currentUser->currentUserId();

                if (
                    $currentOperatorId === $operatorId
                    && ! in_array(PlatformPermission::OPERATORS_MANAGE, $permissionNames, true)
                ) {
                    throw new AuthorizationException('You cannot remove your own operator-management permission.');
                }

                if (
                    ! in_array(PlatformPermission::OPERATORS_MANAGE, $permissionNames, true)
                    && $this->isLastActiveManager($operatorId)
                ) {
                    throw new ConflictHttpException('At least one active platform operator must retain operator-management permission.');
                }

                $this->syncPermissions($operator, $permissionNames);
                $this->incrementVersion($operator, $expectedVersion);
                $operator = $this->reload($operator);
                $this->sessions->revokeAllForOperator(
                    $operatorId,
                    'Platform permissions changed; re-authentication is required.',
                );
                $this->recordAudit('permissions_updated', $operator, $before, $this->snapshot($operator));

                return $operator;
            }, 3);
        });
    }

    public function changeStatus(int $operatorId, int $expectedVersion, string $status, string $reason): UserModel
    {
        return $this->executionContext->runAsControlPlane(function () use ($operatorId, $expectedVersion, $status, $reason): UserModel {
            $status = strtolower(trim($status));
            if (! in_array($status, ['active', 'inactive'], true)) {
                throw ValidationException::withMessages(['status' => ['Operator status must be active or inactive.']]);
            }
            $reason = trim($reason);
            if ($reason === '') {
                throw ValidationException::withMessages(['reason' => ['A reason is required.']]);
            }

            return $this->database->transaction(function () use ($operatorId, $expectedVersion, $status, $reason): UserModel {
                $operator = $this->find($operatorId, true);
                $this->assertVersion($operator, $expectedVersion);
                if ((string) $operator->getAttribute('status') === $status) {
                    return $operator;
                }

                if ($status !== 'active') {
                    if ($this->currentUser->currentUserId() === $operatorId) {
                        throw new AuthorizationException('You cannot deactivate your own platform account.');
                    }
                    if ($this->hasPermission($operatorId, PlatformPermission::OPERATORS_MANAGE)
                        && $this->isLastActiveManager($operatorId)
                    ) {
                        throw new ConflictHttpException('The last active platform manager cannot be deactivated.');
                    }
                }

                $before = $this->snapshot($operator);
                $operator->forceFill([
                    'status' => $status,
                    'row_version' => $expectedVersion + 1,
                    'updated_at' => $this->clock->now(),
                ])->save();
                $operator = $this->reload($operator);
                if ($status !== 'active') {
                    $this->sessions->revokeAllForOperator(
                        $operatorId,
                        'Platform operator deactivated: '.$reason,
                    );
                }
                $this->recordAudit('status_changed', $operator, $before, $this->snapshot($operator), $reason);

                return $operator;
            }, 3);
        });
    }

    /** @return list<string> */
    public function availablePermissions(): array
    {
        return $this->executionContext->runAsControlPlane(function (): array {
            $this->catalogue->synchronize();

            return $this->permissions->newQuery()
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('name')
                ->map(static fn (mixed $name): string => (string) $name)
                ->all();
        });
    }

    private function operatorQuery(): Builder
    {
        return $this->users->newQuery()
            ->whereNull('tenant_id')
            ->where('is_platform_operator', true)
            ->whereNull('deleted_at')
            ->with(['platformPermissionAssignments.permission']);
    }

    /** @param list<string> $permissionNames */
    private function syncPermissions(UserModel $operator, array $permissionNames): void
    {
        $ids = $this->permissions->newQuery()
            ->where('is_active', true)
            ->whereIn('name', $permissionNames)
            ->pluck('id', 'name');
        if ($ids->count() !== count($permissionNames)) {
            throw ValidationException::withMessages(['permissions' => ['One or more platform permissions are invalid or inactive.']]);
        }

        $this->assignments->newQuery()
            ->where('user_id', $operator->getKey())
            ->whereNotIn('platform_permission_id', $ids->values()->all())
            ->delete();

        foreach ($ids as $permissionId) {
            $this->assignments->newQuery()->updateOrCreate(
                ['user_id' => $operator->getKey(), 'platform_permission_id' => (int) $permissionId],
                ['granted_by' => $this->currentUser->currentUserId()],
            );
        }
    }

    private function isLastActiveManager(int $excludingOperatorId): bool
    {
        return ! $this->assignments->newQuery()
            ->where('user_id', '!=', $excludingOperatorId)
            ->whereHas('operator', fn (Builder $query) => $query
                ->whereNull('tenant_id')
                ->where('is_platform_operator', true)
                ->where('status', 'active')
                ->whereNull('deleted_at'))
            ->whereHas('permission', fn (Builder $query) => $query
                ->where('name', PlatformPermission::OPERATORS_MANAGE)
                ->where('is_active', true))
            ->exists();
    }

    private function hasPermission(int $operatorId, string $permission): bool
    {
        return $this->assignments->newQuery()
            ->where('user_id', $operatorId)
            ->whereHas('permission', fn (Builder $query) => $query
                ->where('name', $permission)
                ->where('is_active', true))
            ->exists();
    }

    /** @param list<string> $permissions */
    private function assertKnownPermissions(array $permissions): void
    {
        $unknown = array_values(array_diff($permissions, PlatformPermission::values()));
        if ($unknown !== []) {
            throw ValidationException::withMessages([
                'permissions' => ['Unknown platform permissions: '.implode(', ', $unknown)],
            ]);
        }
    }

    /** @return list<string> */
    private function normalizePermissionNames(mixed $permissions): array
    {
        if (! is_array($permissions)) {
            return [];
        }

        $normalized = [];
        foreach ($permissions as $permission) {
            if (is_string($permission) && trim($permission) !== '') {
                $name = strtolower(trim($permission));
                $normalized[$name] = $name;
            }
        }

        return array_values($normalized);
    }

    private function assertVersion(UserModel $operator, int $expectedVersion): void
    {
        if ((int) $operator->getAttribute('row_version') !== $expectedVersion) {
            throw new ConflictHttpException('The platform operator changed after it was loaded. Refresh and try again.');
        }
    }

    private function incrementVersion(UserModel $operator, int $expectedVersion): void
    {
        $updated = $this->users->newQuery()
            ->whereKey($operator->getKey())
            ->where('row_version', $expectedVersion)
            ->update(['row_version' => $expectedVersion + 1, 'updated_at' => $this->clock->now()]);
        if ($updated !== 1) {
            throw new ConflictHttpException('The platform operator changed after it was loaded. Refresh and try again.');
        }
    }

    private function reload(UserModel $operator): UserModel
    {
        return $this->operatorQuery()->whereKey($operator->getKey())->firstOrFail();
    }

    /** @return array<string, mixed> */
    private function snapshot(UserModel $operator): array
    {
        $permissions = $operator->relationLoaded('platformPermissionAssignments')
            ? $operator->platformPermissionAssignments
                ->map(fn (PlatformOperatorPermissionModel $assignment): ?string => $assignment->permission?->name)
                ->filter()
                ->sort()
                ->values()
                ->all()
            : [];

        return [
            'id' => (int) $operator->getKey(),
            'email' => (string) $operator->getAttribute('platform_login_email'),
            'name' => trim((string) $operator->getAttribute('first_name').' '.(string) $operator->getAttribute('last_name')),
            'status' => (string) $operator->getAttribute('status'),
            'permissions' => $permissions,
            'row_version' => (int) $operator->getAttribute('row_version'),
        ];
    }

    /** @param array<string,mixed>|null $before @param array<string,mixed> $after */
    private function recordAudit(
        string $action,
        UserModel $operator,
        ?array $before,
        array $after,
        ?string $reason = null,
    ): void {
        $this->audit->recordPlatform(new AuditEventData(
            eventName: "platform.operator.{$action}",
            eventCategory: AuditEventCategory::SECURITY,
            sourceModule: 'user',
            subjectType: 'platform_operator',
            subjectId: (string) $operator->getKey(),
            subjectReference: (string) $operator->getAttribute('platform_login_email'),
            changes: ['old' => $before, 'new' => $after],
            metadata: $reason === null ? [] : ['reason' => $reason],
            tags: ['platform', 'operator', 'security'],
        ));
    }

    private function nullableString(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? null : $value;
    }
}
