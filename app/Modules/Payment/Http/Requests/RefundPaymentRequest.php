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
            'refund_number' => ['required', 'string', 'max:100'],
            'refund_date' => ['required', 'date'],
            'amount' => ['required', 'decimal:0,6', 'gt:0'],
            'party_type' => ['nullable', 'string', 'max:150'],
            'party_id' => ['nullable', 'integer', 'min:1'],
            'payment_method_id' => ['nullable', 'integer', 'min:1'],
            'reason' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function toData(int $paymentId): PaymentRefundData
    {
        return new PaymentRefundData(
            paymentId: $paymentId,
            refundNumber: (string) $this->input('refund_number'),
            refundDate: (string) $this->input('refund_date'),
            amount: (string) $this->input('amount'),
            partyType: $this->filled('party_type') ? (string) $this->input('party_type') : null,
            partyId: $this->filled('party_id') ? (int) $this->input('party_id') : null,
            paymentMethodId: $this->filled('payment_method_id') ? (int) $this->input('payment_method_id') : null,
            reason: $this->filled('reason') ? (string) $this->input('reason') : null,
            metadata: $this->input('metadata'),
        );
    }
}
