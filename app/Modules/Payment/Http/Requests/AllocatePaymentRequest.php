<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Payment\Http\Requests\Concerns\BuildsPaymentAllocations;

final class AllocatePaymentRequest extends TenantScopedRequest
{
    use BuildsPaymentAllocations;

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'expected_version' => ['required', 'integer', 'min:1'],
            ...$this->paymentAllocationRules(['required', 'array', 'min:1']),
        ];
    }

    public function toData(): array
    {
        return $this->paymentAllocationData();
    }

    public function expectedVersion(): int
    {
        return (int) $this->input('expected_version');
    }
}
