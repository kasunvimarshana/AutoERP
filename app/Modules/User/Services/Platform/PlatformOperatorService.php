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
use Modules\Core\Contracts\PlatformPermissionCheckerInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\User\Constants\PlatformOperatorStatus;
use Modules\Core\Authorization\PlatformPermission;
use Modules\User\Contracts\PlatformOperatorCredentialProvisionerInterface;
use Modules\User\Contracts\PlatformOperatorSessionRevokerInterface;
use Modules\User\Models\PlatformOperatorModel;
use Modules\User\Models\PlatformOperatorPermissionModel;
use Modules\User\Models\PlatformPermissionModel;
use Modules\User\Services\Platform\Invitations\PlatformOperatorInvitationService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class PlatformOperatorService
{
    public function __construct(
        private readonly PlatformOperatorModel $operators,
        private readonly PlatformPermissionModel $permissions,
        private readonly PlatformOperatorPermissionModel $assignments,
        private readonly PlatformPermissionCheckerInterface $access,
        private readonly PlatformPermissionCatalogSynchronizer $catalogue,
        private readonly PlatformOperatorSessionRevokerInterface $sessions,
        private readonly PlatformOperatorCredentialProvisionerInterface $credentials,
        private readonly PlatformOperatorInvitationService $invitations,
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
                    $prefix = trim($search).'%';
                    $builder->where(function (Builder $nested) use ($prefix): void {
                        $nested->where('email', 'like', $prefix)
                            ->orWhere('first_name', 'like', $prefix)
                            ->orWhere('last_name', 'like', $prefix);
                    });
                })
                ->orderBy('first_name')
                ->orderBy('email');

            return $query->paginate(max(1, min(100, $perPage)), ['*'], 'page', max(1, $page));
        });
    }

    public function find(int $operatorId, bool $lockForUpdate = false): PlatformOperatorModel
    {
        return $this->executionContext->runAsControlPlane(function () use ($operatorId, $lockForUpdate): PlatformOperatorModel {
            $query = $this->operatorQuery()->whereKey($operatorId);
            if ($lockForUpdate) {
                $query->lockForUpdate();
            }

            return $query->first()
                ?? throw (new ModelNotFoundException())->setModel(PlatformOperatorModel::class, [$operatorId]);
        });
    }

    /** @param array<string, mixed> $payload */
    public function create(array $payload): PlatformOperatorModel
    {
        return $this->executionContext->runAsControlPlane(function () use ($payload): PlatformOperatorModel {
            $email = strtolower(trim((string) ($payload['email'] ?? '')));
            $firstName = trim((string) ($payload['first_name'] ?? ''));
            $lastName = $this->nullableString($payload['last_name'] ?? null);
            $permissionNames = $this->normalizePermissionNames($payload['permissions'] ?? []);
            $this->assertKnownPermissions($permissionNames);

            return $this->database->transaction(function () use ($email, $firstName, $lastName, $permissionNames): PlatformOperatorModel {
                $this->catalogue->synchronize();
                if ($this->operators->newQuery()->where('email', $email)->select('id')->lockForUpdate()->first() !== null) {
                    throw ValidationException::withMessages(['email' => ['A platform operator already uses this email address.']]);
                }

                $actorId = $this->currentUser->currentUserId();
                $now = $this->clock->now();
                $operator = $this->operators->newQuery()->create([
                    'row_version' => 1,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'status' => PlatformOperatorStatus::INVITED,
                    'invited_at' => $now,
                    'created_by_operator_id' => $actorId,
                    'updated_by_operator_id' => $actorId,
                ]);
                $this->syncPermissions($operator, $permissionNames);
                $this->invitations->issueForOperator($operator);
                $operator = $this->reload($operator);
                $this->recordAudit('created', $operator, null, $this->snapshot($operator));

                        return $operator;
                    },
                    3,
                );
            },
        );
    }

    /** @param list<string> $permissionNames */
    public function synchronizePermissions(int $operatorId, int $expectedVersion, array $permissionNames): PlatformOperatorModel
    {
        return $this->executionContext->runAsControlPlane(function () use ($operatorId, $expectedVersion, $permissionNames): PlatformOperatorModel {
            $permissionNames = $this->normalizePermissionNames($permissionNames);
            $this->assertKnownPermissions($permissionNames);

            return $this->database->transaction(function () use ($operatorId, $expectedVersion, $permissionNames): PlatformOperatorModel {
                $this->lockActiveOperators();
                $operator = $this->find($operatorId, true);
                $this->assertVersion($operator, $expectedVersion);
                $before = $this->snapshot($operator);

                if ($this->access->allows($operatorId, PlatformPermission::OPERATORS_MANAGE)
                    && ! in_array(PlatformPermission::OPERATORS_MANAGE, $permissionNames, true)
                    && $this->isLastActiveManager($operatorId)
                ) {
                    throw new ConflictHttpException('The last active platform manager must retain operator-management permission.');
                }

                $this->syncPermissions($operator, $permissionNames);
                $operator->forceFill([
                    'row_version' => $expectedVersion + 1,
                    'updated_by_operator_id' => $this->currentUser->currentUserId(),
                    'updated_at' => $this->clock->now(),
                ])->save();
                $operator = $this->reload($operator);
                $this->sessions->revokeAllForOperator($operatorId, 'Platform permissions changed.');
                $this->recordAudit('permissions_updated', $operator, $before, $this->snapshot($operator));

                return $operator;
            }, 3);
        });
    }

    public function changeStatus(int $operatorId, int $expectedVersion, string $status, string $reason): PlatformOperatorModel
    {
        return $this->executionContext->runAsControlPlane(function () use ($operatorId, $expectedVersion, $status, $reason): PlatformOperatorModel {
            $status = strtolower(trim($status));
            if (! in_array($status, [PlatformOperatorStatus::ACTIVE, PlatformOperatorStatus::INACTIVE], true)) {
                throw ValidationException::withMessages(['status' => ['Operator status must be active or inactive.']]);
            }
            $reason = trim($reason);
            if ($reason === '') {
                throw ValidationException::withMessages(['reason' => ['A reason is required.']]);
            }

            return $this->database->transaction(function () use ($operatorId, $expectedVersion, $status, $reason): PlatformOperatorModel {
                $this->lockActiveOperators();
                $operator = $this->find($operatorId, true);
                $this->assertVersion($operator, $expectedVersion);
                $currentStatus = (string) $operator->getAttribute('status');
                if ($currentStatus === $status) {
                    return $operator;
                }
                if ($currentStatus === PlatformOperatorStatus::INVITED) {
                    throw new ConflictHttpException('Invited operators must complete or revoke their invitation.');
                }
                if ($status === PlatformOperatorStatus::ACTIVE && $operator->getAttribute('credentials_ready_at') === null) {
                    throw new ConflictHttpException('The operator must complete credential setup before activation.');
                }
                if ($status === PlatformOperatorStatus::INACTIVE) {
                    if ($this->currentUser->currentUserId() === $operatorId) {
                        throw new AuthorizationException('You cannot deactivate your own platform account.');
                    }
                    if ($this->access->allows($operatorId, PlatformPermission::OPERATORS_MANAGE)
                        && $this->isLastActiveManager($operatorId)
                    ) {
                        throw new ConflictHttpException('The last active platform manager cannot be deactivated.');
                    }
                }

                $before = $this->snapshot($operator);
                $now = $this->clock->now();
                $operator->forceFill([
                    'status' => $status,
                    'activated_at' => $status === PlatformOperatorStatus::ACTIVE ? $now : $operator->getAttribute('activated_at'),
                    'deactivated_at' => $status === PlatformOperatorStatus::INACTIVE ? $now : null,
                    'row_version' => $expectedVersion + 1,
                    'updated_by_operator_id' => $this->currentUser->currentUserId(),
                    'updated_at' => $now,
                ])->save();
                if ($status === PlatformOperatorStatus::INACTIVE) {
                    $this->sessions->revokeAllForOperator($operatorId, 'Platform operator deactivated: '.$reason);
                }
                $operator = $this->reload($operator);
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

            return $this->permissions->newQuery()->where('is_active', true)->orderBy('name')
                ->pluck('name')->map(static fn (mixed $name): string => (string) $name)->all();
        });
    }

    private function operatorQuery(): Builder
    {
        return $this->operators->newQuery()->with([
            'permissionAssignments.permission',
            'latestInvitation.deliveries' => fn ($query) => $query->latest('attempt_number'),
        ]);
    }

    public function recoverAccess(
        int $operatorId,
        int $expectedVersion,
        string $reason,
    ): PlatformOperatorModel {
        return $this->executionContext->runAsControlPlane(
            function () use ($operatorId, $expectedVersion, $reason): PlatformOperatorModel {
                $reason = trim($reason);
                if (mb_strlen($reason) < 10) {
                    throw ValidationException::withMessages([
                        'reason' => [
                            'A security-recovery reason of at least 10 characters is required.',
                        ],
                    ]);
                }

                return $this->database->transaction(
                    function () use ($operatorId, $expectedVersion, $reason): PlatformOperatorModel {
                        $this->lockActiveOperators();
                        $operator = $this->find($operatorId, true);
                        $this->assertVersion($operator, $expectedVersion);

                        if ($this->currentUser->currentUserId() === $operatorId) {
                            throw new AuthorizationException(
                                'You cannot start security recovery for your own platform account.',
                            );
                        }

                        if (
                            $this->access->allows($operatorId, PlatformPermission::OPERATORS_MANAGE)
                            && (string) $operator->getAttribute('status') === PlatformOperatorStatus::ACTIVE
                            && $this->isLastActiveManager($operatorId)
                        ) {
                            throw new ConflictHttpException(
                                'Security recovery cannot remove the last active platform manager.',
                            );
                        }

                        $before = $this->snapshot($operator);
                        $recoveryReason = 'Platform access recovery: '.$reason;
                        $this->sessions->revokeAllForOperator($operatorId, $recoveryReason);
                        $this->credentials->revoke($operatorId);

                        $now = $this->clock->now();
                        $operator->forceFill([
                            'status' => PlatformOperatorStatus::INVITED,
                            'credentials_ready_at' => null,
                            'invited_at' => $now,
                            'activated_at' => null,
                            'deactivated_at' => $now,
                            'row_version' => $expectedVersion + 1,
                            'updated_by_operator_id' => $this->currentUser->currentUserId(),
                            'updated_at' => $now,
                        ])->save();

                        $this->invitations->issueForOperator($operator);
                        $operator = $this->reload($operator);
                        $this->recordAudit(
                            'security_recovery_started',
                            $operator,
                            $before,
                            $this->snapshot($operator),
                            $reason,
                        );

                        return $operator;
                    },
                    3,
                );
            },
        );
    }

    /** @param list<string> $permissionNames */
    private function syncPermissions(PlatformOperatorModel $operator, array $permissionNames): void
    {
        $ids = $this->permissions->newQuery()->where('is_active', true)
            ->whereIn('name', $permissionNames)->pluck('id', 'name');
        if ($ids->count() !== count($permissionNames)) {
            throw ValidationException::withMessages(['permissions' => ['One or more platform permissions are invalid or inactive.']]);
        }

        $this->assignments->newQuery()->where('platform_operator_id', $operator->getKey())
            ->whereNotIn('platform_permission_id', $ids->values()->all())->delete();
        foreach ($ids as $permissionId) {
            $this->assignments->newQuery()->updateOrCreate(
                ['platform_operator_id' => $operator->getKey(), 'platform_permission_id' => (int) $permissionId],
                ['granted_by_operator_id' => $this->currentUser->currentUserId()],
            );
        }
    }

    private function lockActiveOperators(): void
    {
        $this->operators->newQuery()->where('status', PlatformOperatorStatus::ACTIVE)
            ->orderBy('id')->lockForUpdate()->get(['id']);
    }

    private function isLastActiveManager(int $excludingOperatorId): bool
    {
        return ! $this->assignments->newQuery()
            ->where('platform_operator_id', '!=', $excludingOperatorId)
            ->whereHas('operator', fn (Builder $query) => $query
                ->where('status', PlatformOperatorStatus::ACTIVE)
                ->whereNotNull('credentials_ready_at'))
            ->whereHas('permission', fn (Builder $query) => $query
                ->where('name', PlatformPermission::OPERATORS_MANAGE)->where('is_active', true))
            ->exists();
    }

    /** @param list<string> $permissions */
    private function assertKnownPermissions(array $permissions): void
    {
        $unknown = array_values(array_diff($permissions, PlatformPermission::values()));
        if ($unknown !== []) {
            throw ValidationException::withMessages(['permissions' => ['Unknown platform permissions: '.implode(', ', $unknown)]]);
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

    private function assertVersion(PlatformOperatorModel $operator, int $expectedVersion): void
    {
        if ((int) $operator->getAttribute('row_version') !== $expectedVersion) {
            throw new ConflictHttpException('The platform operator changed after it was loaded. Refresh and try again.');
        }
    }

    private function reload(PlatformOperatorModel $operator): PlatformOperatorModel
    {
        return $this->operatorQuery()->whereKey($operator->getKey())->firstOrFail();
    }

    /** @return array<string, mixed> */
    private function snapshot(PlatformOperatorModel $operator): array
    {
        $permissions = $operator->relationLoaded('permissionAssignments')
            ? $operator->permissionAssignments->map(
                fn (PlatformOperatorPermissionModel $assignment): ?string => $assignment->permission?->name,
            )->filter()->sort()->values()->all()
            : [];

        return [
            'id' => (int) $operator->getKey(),
            'email' => (string) $operator->getAttribute('email'),
            'name' => trim((string) $operator->getAttribute('first_name').' '.(string) $operator->getAttribute('last_name')),
            'status' => (string) $operator->getAttribute('status'),
            'permissions' => $permissions,
            'row_version' => (int) $operator->getAttribute('row_version'),
        ];
    }

    /** @param array<string,mixed>|null $before @param array<string,mixed> $after */
    private function recordAudit(string $action, PlatformOperatorModel $operator, ?array $before, array $after, ?string $reason = null): void
    {
        $this->audit->recordPlatform(new AuditEventData(
            eventName: "platform.operator.{$action}",
            eventCategory: AuditEventCategory::SECURITY,
            sourceModule: 'user',
            subjectType: 'platform_operator',
            subjectId: (string) $operator->getKey(),
            subjectReference: (string) $operator->getAttribute('email'),
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
