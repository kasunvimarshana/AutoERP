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
            'reference_number' => ['nullable', 'string', 'max:150'],
            'external_bank_name' => ['nullable', 'string', 'max:150'],
            'external_bank_branch' => ['nullable', 'string', 'max:150'],
            'instrument_number' => ['nullable', 'string', 'max:150'],
            'instrument_date' => ['nullable', 'date'],
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
            referenceNumber: $this->stringOrNull('reference_number'),
            externalBankName: $this->stringOrNull('external_bank_name'),
            externalBankBranch: $this->stringOrNull('external_bank_branch'),
            instrumentNumber: $this->stringOrNull('instrument_number'),
            instrumentDate: $this->stringOrNull('instrument_date'),
            reason: trim((string) $this->input('reason')),
            refundedBy: $this->currentUserId(),
        );
    }

    private function stringOrNull(string $key): ?string
    {
        return $this->filled($key) ? trim((string) $this->input($key)) : null;
    }
}
