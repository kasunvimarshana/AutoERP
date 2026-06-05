<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Core\Application\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentUserContextAccessorInterface;

final class FinancialServiceSupport
{
    public function __construct(
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
        private readonly CurrentUserContextAccessorInterface $currentUser,
    ) {}

    public function tenantId(): int
    {
        $tenantId = $this->currentTenant->currentTenantId();
        if ($tenantId === null) {
            throw ValidationException::withMessages(['tenant_id' => ['Tenant context is required.']]);
        }

        return $tenantId;
    }

    public function organizationUnitId(?int $requested = null): ?int
    {
        $tenantId = $this->tenantId();
        $organizationUnitId = $requested ?? $this->currentOrganizationUnit->currentOrganizationUnitId();
        if ($organizationUnitId !== null && ! DB::table('organization_units')->where('tenant_id', $tenantId)->where('id', $organizationUnitId)->exists()) {
            throw ValidationException::withMessages(['organization_unit_id' => ['The selected organization unit does not belong to the active tenant.']]);
        }

        return $organizationUnitId;
    }

    public function userId(): ?int
    {
        return $this->currentUser->currentUserId();
    }

    public function assertTenantRow(string $table, int $id, string $field): void
    {
        $query = DB::table($table)->where('tenant_id', $this->tenantId())->where('id', $id);
        if (DB::getSchemaBuilder()->hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if (! $query->exists()) {
            throw ValidationException::withMessages([$field => ["The selected $field does not belong to the active tenant."]]);
        }
    }

    public function accountId(string $role): int
    {
        $code = (string) config("finance.posting_accounts.$role");
        if ($code === '') {
            throw ValidationException::withMessages(['account' => ["Posting account code is not configured for $role."]]);
        }

        $accountId = DB::table('accounts')
            ->where('tenant_id', $this->tenantId())
            ->where('code', $code)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->value('id');

        if ($accountId === null) {
            throw ValidationException::withMessages(['account' => ["Posting account $code was not found for $role."]]);
        }

        return (int) $accountId;
    }

    public function nextNumber(string $prefix, string $table, string $column): string
    {
        return $prefix.'-'.now()->format('YmdHisv').'-'.str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT);
    }
}
