<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Finance\DTOs\AccountAssignmentData;
use Modules\Finance\Models\FinanceAccount;
use Modules\Finance\Models\FinanceAccountAssignment;
use Modules\Finance\Models\FinanceAccountRole;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class AccountAssignmentService
{
    private const GLOBAL_SCOPE = 'global';
    private const NO_CONTEXT = 'none';

    public function save(
        AccountAssignmentData $data,
        ?FinanceAccountAssignment $assignment = null,
    ): FinanceAccountAssignment {
        return DB::transaction(function () use ($data, $assignment): FinanceAccountAssignment {
            $role = FinanceAccountRole::query()->lockForUpdate()->findOrFail($data->accountRoleId);
            $account = FinanceAccount::query()->lockForUpdate()->findOrFail($data->accountId);
            $this->validate($data, $role, $account);

            if ($assignment instanceof FinanceAccountAssignment) {
                $assignment = FinanceAccountAssignment::query()->lockForUpdate()->findOrFail($assignment->getKey());
                $this->assertVersion($assignment, $data->expectedVersion);
                if ((int) $assignment->tenant_id !== $data->tenantId) {
                    throw new InvalidArgumentException('Finance account assignment tenant cannot be changed.');
                }
            }

            $scopeKey = $this->scopeKey(
                $data->organizationUnitId,
                (string) $role->code,
                $data->contextType,
                $data->contextId,
            );
            $this->lockScope($data->tenantId, $scopeKey);
            $this->assertNoOverlap($data, $scopeKey, $assignment?->getKey());

            $values = [
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'account_role_id' => $role->getKey(),
                'account_id' => $account->getKey(),
                'context_type' => $this->normalizeContextType($data->contextType),
                'context_id' => $data->contextId,
                'scope_key' => $scopeKey,
                'effective_from' => $data->effectiveFrom,
                'effective_to' => $data->effectiveTo,
                'is_active' => $data->isActive,
                'description' => $data->description,
                'updated_by' => $data->actorId,
            ];

            if ($assignment instanceof FinanceAccountAssignment) {
                $values['row_version'] = (int) $assignment->row_version + 1;
                $assignment->forceFill($values)->save();
            } else {
                $values['created_by'] = $data->actorId;
                $assignment = FinanceAccountAssignment::query()->forceCreate($values);
            }

            return $assignment->refresh()->load(['role', 'account', 'organizationUnit']);
        }, 3);
    }

    public function resolve(
        int $tenantId,
        ?int $organizationUnitId,
        FinanceAccountRole|string $role,
        string $postingDate,
        ?string $contextType = null,
        ?int $contextId = null,
    ): FinanceAccountAssignment {
        $roleModel = $role instanceof FinanceAccountRole
            ? $role
            : FinanceAccountRole::query()
                ->where('tenant_id', $tenantId)
                ->where('code', trim($role))
                ->where('is_active', true)
                ->first();

        if (! $roleModel instanceof FinanceAccountRole || (int) $roleModel->tenant_id !== $tenantId) {
            throw new InvalidArgumentException('Finance account role is missing or inactive for this tenant.');
        }

        $contextType = $this->normalizeContextType($contextType);
        if (($contextType === null) !== ($contextId === null)) {
            throw new InvalidArgumentException('Finance assignment context type and ID must be provided together.');
        }

        $query = FinanceAccountAssignment::query()
            ->with(['role', 'account'])
            ->where('tenant_id', $tenantId)
            ->where('account_role_id', $roleModel->getKey())
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $postingDate)
            ->where(function (Builder $scope) use ($postingDate): void {
                $scope->whereNull('effective_to')->orWhereDate('effective_to', '>=', $postingDate);
            })
            ->where(function (Builder $scope) use ($organizationUnitId): void {
                $scope->whereNull('organization_unit_id');
                if ($organizationUnitId !== null) {
                    $scope->orWhere('organization_unit_id', $organizationUnitId);
                }
            })
            ->where(function (Builder $scope) use ($contextType, $contextId): void {
                $scope->where(function (Builder $generic): void {
                    $generic->whereNull('context_type')->whereNull('context_id');
                });
                if ($contextType !== null && $contextId !== null) {
                    $scope->orWhere(function (Builder $specific) use ($contextType, $contextId): void {
                        $specific->where('context_type', $contextType)->where('context_id', $contextId);
                    });
                }
            });

        $assignments = $query->get();
        $assignment = $assignments
            ->sortByDesc(fn (FinanceAccountAssignment $candidate): int => $this->priority(
                $candidate,
                $organizationUnitId,
                $contextType,
                $contextId,
            ))
            ->first();

        if (! $assignment instanceof FinanceAccountAssignment) {
            throw new InvalidArgumentException(
                "No effective Finance account assignment exists for role [{$roleModel->code}].",
            );
        }

        $account = $assignment->account;
        if (! $account instanceof FinanceAccount || ! $account->is_active || ! $account->is_posting_account) {
            throw new InvalidArgumentException(
                "Finance account assignment for role [{$roleModel->code}] is inactive or not postable.",
            );
        }
        if ((int) $account->tenant_id !== $tenantId) {
            throw new InvalidArgumentException('Resolved Finance account belongs to a different tenant.');
        }
        if ($account->organization_unit_id !== null
            && (int) $account->organization_unit_id !== (int) $organizationUnitId) {
            throw new InvalidArgumentException('Resolved Finance account belongs to a different organization unit.');
        }

        return $assignment;
    }

    public function scopeKey(
        ?int $organizationUnitId,
        string $roleCode,
        ?string $contextType,
        ?int $contextId,
    ): string {
        $scope = $organizationUnitId === null ? self::GLOBAL_SCOPE : 'ou:'.$organizationUnitId;
        $contextType = $this->normalizeContextType($contextType);
        $context = $contextType === null || $contextId === null
            ? self::NO_CONTEXT
            : $contextType.':'.$contextId;

        return hash('sha256', implode('|', [$scope, strtoupper(trim($roleCode)), $context]));
    }

    private function validate(
        AccountAssignmentData $data,
        FinanceAccountRole $role,
        FinanceAccount $account,
    ): void {
        if ($data->tenantId < 1 || trim($data->effectiveFrom) === '') {
            throw new InvalidArgumentException('Finance account assignment tenant and effective start date are required.');
        }
        if (($data->contextType === null) !== ($data->contextId === null)) {
            throw new InvalidArgumentException('Finance assignment context type and ID must be provided together.');
        }
        if ($data->effectiveTo !== null && $data->effectiveTo < $data->effectiveFrom) {
            throw new InvalidArgumentException('Finance account assignment end date cannot precede its start date.');
        }
        if ((int) $role->tenant_id !== $data->tenantId || ! $role->is_active) {
            throw new InvalidArgumentException('Finance account role belongs to a different tenant or is inactive.');
        }
        if ((int) $account->tenant_id !== $data->tenantId) {
            throw new InvalidArgumentException('Finance account belongs to a different tenant.');
        }
        if ($account->organization_unit_id !== null
            && (int) $account->organization_unit_id !== (int) $data->organizationUnitId) {
            throw new InvalidArgumentException('Finance account belongs to a different organization unit.');
        }
        if (! $account->is_active || ! $account->is_posting_account) {
            throw new InvalidArgumentException('Finance account assignment requires an active posting account.');
        }
    }

    private function assertNoOverlap(
        AccountAssignmentData $data,
        string $scopeKey,
        ?int $ignoreId,
    ): void {
        $overlap = FinanceAccountAssignment::query()
            ->where('tenant_id', $data->tenantId)
            ->where('scope_key', $scopeKey)
            ->where('is_active', true)
            ->when($ignoreId !== null, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->whereDate('effective_from', '<=', $data->effectiveTo ?? '9999-12-31')
            ->where(function (Builder $query) use ($data): void {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $data->effectiveFrom);
            })
            ->exists();

        if ($overlap) {
            throw new ConflictHttpException('Finance account assignment effective period overlaps an existing assignment.');
        }
    }

    private function lockScope(int $tenantId, string $scopeKey): void
    {
        FinanceAccountAssignment::query()
            ->where('tenant_id', $tenantId)
            ->where('scope_key', $scopeKey)
            ->lockForUpdate()
            ->get(['id']);
    }

    private function assertVersion(FinanceAccountAssignment $assignment, ?int $expectedVersion): void
    {
        if ($expectedVersion === null || $expectedVersion !== (int) $assignment->row_version) {
            throw new ConflictHttpException('Finance account assignment was changed by another request.');
        }
    }

    private function priority(
        FinanceAccountAssignment $assignment,
        ?int $organizationUnitId,
        ?string $contextType,
        ?int $contextId,
    ): int {
        $specificContext = $contextType !== null
            && $contextId !== null
            && $assignment->context_type === $contextType
            && (int) $assignment->context_id === $contextId;
        $specificOrganization = $organizationUnitId !== null
            && (int) $assignment->organization_unit_id === $organizationUnitId;

        return match (true) {
            $specificContext && $specificOrganization => 400,
            $specificContext && $assignment->organization_unit_id === null => 300,
            $assignment->context_type === null && $specificOrganization => 200,
            default => 100,
        };
    }

    private function normalizeContextType(?string $contextType): ?string
    {
        $value = trim((string) $contextType);

        return $value === '' ? null : strtolower($value);
    }
}
