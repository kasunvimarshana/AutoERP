<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Customer\DTOs\CustomerStatusChangeData;
use Modules\Customer\Enums\CustomerStatus;

final class ChangeCustomerStatusRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', Rule::enum(CustomerStatus::class)],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function toData(): CustomerStatusChangeData
    {
        return new CustomerStatusChangeData(
            newStatus: CustomerStatus::from((string) $this->input('status')),
            reason: $this->filled('reason') ? (string) $this->input('reason') : null,
            changedBy: $this->currentUserId(),
        );
    }
}
