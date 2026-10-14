<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class ReversePaymentAllocationRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'expected_payment_version' => ['required', 'integer', 'min:1'],
            'expected_allocation_version' => ['required', 'integer', 'min:1'],
            'reversal_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function expectedPaymentVersion(): int
    {
        return (int) $this->input('expected_payment_version');
    }

    public function expectedAllocationVersion(): int
    {
        return (int) $this->input('expected_allocation_version');
    }

    public function reversalDate(): string
    {
        return (string) $this->input('reversal_date');
    }

    public function reason(): string
    {
        return trim((string) $this->input('reason'));
    }
}
