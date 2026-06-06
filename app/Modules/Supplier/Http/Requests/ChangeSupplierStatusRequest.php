<?php

declare(strict_types=1);

namespace Modules\Supplier\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Supplier\DTOs\SupplierStatusChangeData;
use Modules\Supplier\Enums\SupplierStatus;

final class ChangeSupplierStatusRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', Rule::enum(SupplierStatus::class)],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function toData(): SupplierStatusChangeData
    {
        return new SupplierStatusChangeData(
            newStatus: SupplierStatus::from((string) $this->input('status')),
            reason: $this->filled('reason') ? (string) $this->input('reason') : null,
            changedBy: $this->currentUserId(),
        );
    }
}
