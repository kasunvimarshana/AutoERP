<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Finance\DTOs\AccountAssignmentData;

final class UpsertAccountAssignmentRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        $updating = is_numeric($this->route('assignment'));

        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'expected_version' => [$updating ? 'required' : 'nullable', 'integer', 'min:1'],
            'account_role_id' => ['required', 'integer', 'min:1', $this->tenantExists('finance_account_roles', 'id')],
            'account_id' => ['required', 'integer', 'min:1', $this->tenantExists('finance_accounts', 'id')],
            'context_type' => ['nullable', 'string', 'max:100', 'required_with:context_id'],
            'context_id' => ['nullable', 'integer', 'min:1', 'required_with:context_type'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function toData(): AccountAssignmentData
    {
        return new AccountAssignmentData(
            tenantId: $this->tenantId(),
            accountRoleId: (int) $this->input('account_role_id'),
            accountId: (int) $this->input('account_id'),
            effectiveFrom: (string) $this->input('effective_from'),
            organizationUnitId: $this->organizationUnitId(),
            contextType: $this->filled('context_type') ? (string) $this->input('context_type') : null,
            contextId: $this->filled('context_id') ? (int) $this->input('context_id') : null,
            effectiveTo: $this->filled('effective_to') ? (string) $this->input('effective_to') : null,
            isActive: $this->boolean('is_active', true),
            description: $this->filled('description') ? (string) $this->input('description') : null,
            actorId: $this->currentUserId(),
            expectedVersion: $this->filled('expected_version') ? (int) $this->input('expected_version') : null,
        );
    }
}
