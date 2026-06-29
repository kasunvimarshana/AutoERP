<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Payment\DTOs\PaymentRefundData;

final class RefundPaymentRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'expected_version' => ['required', 'integer', 'min:1'],
            'refund_date' => ['required', 'date'],
            'amount' => ['required', 'decimal:0,6', 'gt:0'],
            'payment_method_id' => ['nullable', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:1000'],
            'refund_number' => ['prohibited'],
            'party_type' => ['prohibited'],
            'party_id' => ['prohibited'],
            'metadata' => ['prohibited'],
        ];
    }

    public function toData(int $paymentId): PaymentRefundData
    {
        return new PaymentRefundData(
            paymentId: $paymentId,
            expectedVersion: (int) $this->input('expected_version'),
            refundDate: (string) $this->input('refund_date'),
            amount: (string) $this->input('amount'),
            paymentMethodId: $this->filled('payment_method_id') ? (int) $this->input('payment_method_id') : null,
            reason: trim((string) $this->input('reason')),
            refundedBy: $this->currentUserId(),
        );
    }
}
