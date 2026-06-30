<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Finance\Models\FinanceAccount;
use Modules\Finance\Models\FinanceAccountAssignment;
use Modules\Finance\Models\FinanceAccountRole;

final class AccountRoleAssignmentService
{
    public function saveRole(
        int $tenantId,
        string $code,
        string $name,
        ?string $description,
        bool $isActive,
        ?FinanceAccountRole $role = null,
    ): FinanceAccountRole {
        return DB::transaction(function () use ($tenantId, $code, $name, $description, $isActive, $role): FinanceAccountRole {
            $normalizedCode = mb_strtolower(trim($code));
            if ($normalizedCode === '') {
                throw new InvalidArgumentException('Finance account role code is required.');
            }
            if (trim($name) === '') {
                throw new InvalidArgumentException('Finance account role name is required.');
            }

            $role ??= new FinanceAccountRole;
            if ($role->exists && (int) $role->tenant_id !== $tenantId) {
                throw new InvalidArgumentException('Finance account role tenant cannot be changed.');
            }

            $duplicate = FinanceAccountRole::query()
                ->where('tenant_id', $tenantId)
                ->where('code', $normalizedCode)
                ->when($role->exists, fn ($query) => $query->whereKeyNot($role->getKey()))
                ->exists();
            if ($duplicate) {
                throw new InvalidArgumentException('Finance account role code already exists for this tenant.');
            }

            $role->forceFill([
                'tenant_id' => $tenantId,
                'code' => $normalizedCode,
                'name' => trim($name),
                'description' => $description,
                'is_active' => $isActive,
            ])->save();

            return $role->refresh();
        });
    }

    public function assign(
        int $tenantId,
        ?int $organizationUnitId,
        int $accountRoleId,
        int $accountId,
        string $effectiveFrom,
        ?string $effectiveTo = null,
        ?int $createdBy = null,
    ): FinanceAccountAssignment {
        return DB::transaction(function () use (
            $tenantId,
            $organizationUnitId,
            $accountRoleId,
            $accountId,
            $effectiveFrom,
            $effectiveTo,
            $createdBy,
        ): FinanceAccountAssignment {
            $role = FinanceAccountRole::query()->lockForUpdate()->findOrFail($accountRoleId);
            $this->assertRole($role, $tenantId);
            $account = FinanceAccount::query()->lockForUpdate()->findOrFail($accountId);
            $this->assertAccount($account, $tenantId, $organizationUnitId);

            $from = CarbonImmutable::parse($effectiveFrom)->startOfDay();
            $to = $effectiveTo !== null ? CarbonImmutable::parse($effectiveTo)->startOfDay() : null;
            if ($to !== null && $to->lt($from)) {
                throw new InvalidArgumentException('Finance account assignment effective end cannot precede its start.');
            }

            $overlap = FinanceAccountAssignment::query()
                ->where('tenant_id', $tenantId)
                ->where('account_role_id', $accountRoleId)
                ->where('is_active', true)
                ->when(
                    $organizationUnitId === null,
                    fn ($query) => $query->whereNull('organization_unit_id'),
                    fn ($query) => $query->where('organization_unit_id', $organizationUnitId),
                )
                ->when(
                    $to !== null,
                    fn ($query) => $query->whereDate('effective_from', '<=', $to->toDateString()),
                )
                ->where(function ($query) use ($from): void {
                    $query->whereNull('effective_to')
                        ->orWhereDate('effective_to', '>=', $from->toDateString());
                })
                ->exists();
            if ($overlap) {
                throw new InvalidArgumentException('Finance account assignment effective period overlaps an existing assignment for this role and scope.');
            }

            return FinanceAccountAssignment::query()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'account_role_id' => $accountRoleId,
                'account_id' => $accountId,
                'effective_from' => $from->toDateString(),
                'effective_to' => $to?->toDateString(),
                'is_active' => true,
                'created_by' => $createdBy,
            ])->load(['role', 'account']);
        });
    }

    public function endAssignment(
        FinanceAccountAssignment $assignment,
        string $effectiveTo,
        ?int $endedBy = null,
    ): FinanceAccountAssignment {
        return DB::transaction(function () use ($assignment, $effectiveTo, $endedBy): FinanceAccountAssignment {
            FinanceAccountRole::query()->lockForUpdate()->findOrFail($assignment->account_role_id);
            $assignment = FinanceAccountAssignment::query()->lockForUpdate()->findOrFail($assignment->getKey());
            $to = CarbonImmutable::parse($effectiveTo)->startOfDay();
            $from = CarbonImmutable::parse((string) $assignment->effective_from)->startOfDay();
            if ($to->lt($from)) {
                throw new InvalidArgumentException('Finance account assignment effective end cannot precede its start.');
            }
            if ($assignment->effective_to !== null
                && $to->gt(CarbonImmutable::parse((string) $assignment->effective_to)->startOfDay())) {
                throw new InvalidArgumentException('Finance account assignment end date can only be shortened.');
            }

            $assignment->forceFill([
                'effective_to' => $to->toDateString(),
                'ended_by' => $endedBy,
            ])->save();

            return $assignment->refresh()->load(['role', 'account']);
        });
    }

    public function resolveByCode(
        int $tenantId,
        ?int $organizationUnitId,
        string $roleCode,
        string $effectiveDate,
    ): FinanceAccount {
        $role = FinanceAccountRole::query()
            ->where('tenant_id', $tenantId)
            ->where('code', mb_strtolower(trim($roleCode)))
            ->where('is_active', true)
            ->first();
        if (! $role instanceof FinanceAccountRole) {
            throw new InvalidArgumentException("Finance account role [{$roleCode}] is missing or inactive.");
        }

        return $this->resolve($tenantId, $organizationUnitId, $role, $effectiveDate);
    }

    public function resolve(
        int $tenantId,
        ?int $organizationUnitId,
        FinanceAccountRole $role,
        string $effectiveDate,
    ): FinanceAccount {
        $this->assertRole($role, $tenantId);
        $date = CarbonImmutable::parse($effectiveDate)->toDateString();

        $query = FinanceAccountAssignment::query()
            ->with('account')
            ->where('tenant_id', $tenantId)
            ->where('account_role_id', $role->getKey())
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $date)
            ->where(function ($scope) use ($date): void {
                $scope->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date);
            });

        if ($organizationUnitId === null) {
            $query->whereNull('organization_unit_id');
        } else {
            $query->where(function ($scope) use ($organizationUnitId): void {
                $scope->where('organization_unit_id', $organizationUnitId)
                    ->orWhereNull('organization_unit_id');
            })->orderByRaw('organization_unit_id IS NULL ASC');
        }

        $assignments = $query
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->get();
        if ($assignments->isEmpty()) {
            throw new InvalidArgumentException(
                "Finance account role [{$role->code}] has no effective assignment for {$date}.",
            );
        }

        $selectedScope = $assignments->first()->organization_unit_id;
        $sameScope = $assignments->filter(
            static fn (FinanceAccountAssignment $candidate): bool => $candidate->organization_unit_id === $selectedScope,
        );
        if ($sameScope->count() !== 1) {
            throw new InvalidArgumentException(
                "Finance account role [{$role->code}] has ambiguous effective assignments for {$date}.",
            );
        }

        /** @var FinanceAccountAssignment $assignment */
        $assignment = $sameScope->first();
        $account = $assignment->account;
        if (! $account instanceof FinanceAccount) {
            throw new InvalidArgumentException("Finance account role [{$role->code}] assignment has no account.");
        }
        $this->assertAccount($account, $tenantId, $assignment->organization_unit_id);

        return $account;
    }

    private function assertRole(FinanceAccountRole $role, int $tenantId): void
    {
        if ((int) $role->tenant_id !== $tenantId || ! (bool) $role->is_active) {
            throw new InvalidArgumentException('Finance account role must be active in the posting tenant.');
        }
    }

    private function assertAccount(FinanceAccount $account, int $tenantId, ?int $organizationUnitId): void
    {
        if ((int) $account->tenant_id !== $tenantId
            || $account->organization_unit_id !== $organizationUnitId) {
            throw new InvalidArgumentException('Finance account assignment account belongs to a different scope.');
        }
        if (! (bool) $account->is_active || ! (bool) $account->is_posting_account) {
            throw new InvalidArgumentException('Finance account assignment requires an active posting account.');
        }
    }
}
