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
            'reversal_number' => ['required', 'string', 'max:100'],
            'reversal_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function toData(int $paymentId): PaymentReversalData
    {
        return new PaymentReversalData(
            paymentId: $paymentId,
            reversalNumber: (string) $this->input('reversal_number'),
            reversalDate: (string) $this->input('reversal_date'),
            reason: (string) $this->input('reason'),
            reversedBy: $this->currentUserId(),
        );
    }
}
