<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Payment\DTOs\PaymentReversalData;

final class ReversePaymentRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'expected_version' => ['required', 'integer', 'min:1'],
            'reversal_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:1000'],
            'reversal_number' => ['prohibited'],
            'metadata' => ['prohibited'],
        ];
    }

    public function toData(int $paymentId): PaymentReversalData
    {
        return new PaymentReversalData(
            paymentId: $paymentId,
            expectedVersion: (int) $this->input('expected_version'),
            reversalDate: (string) $this->input('reversal_date'),
            reason: trim((string) $this->input('reason')),
            reversedBy: $this->currentUserId(),
        );
    }
}
