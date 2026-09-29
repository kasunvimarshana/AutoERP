<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Payment\Constants\PaymentIdempotency;
use Modules\Payment\Http\Requests\Concerns\BuildsPaymentLines;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Data\AgreementContext;

final class DepositReceiptRequest extends TenantScopedRequest
{
    use BuildsPaymentLines;

    private const PAYMENT_REFERENCE_LENGTH = 150;

    public function rules(): array
    {
        return [
            'expected_version' => ['required', 'integer', 'min:1'],
            'payment_date' => ['required', 'date_format:'.AgreementFields::DATE_FORMAT],
            'exchange_rate' => ['required', 'regex:'.AgreementFields::DECIMAL_PATTERN, 'gt:0'],
            'reference_number' => ['nullable', 'string', 'max:'.self::PAYMENT_REFERENCE_LENGTH],
            'notes' => ['nullable', 'string', 'max:'.AgreementFields::NOTES_LENGTH],
            PaymentIdempotency::REQUEST_ATTRIBUTE => ['required', 'string', 'max:'.PaymentIdempotency::MAX_KEY_LENGTH],
            ...$this->paymentLineRules(),
            'party_id' => ['prohibited'], 'currency_id' => ['prohibited'], 'source_id' => ['prohibited'],
            'source_type' => ['prohibited'], 'payment_type' => ['prohibited'], 'direction' => ['prohibited'],
            'allocations' => ['prohibited'], 'metadata' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->merge([PaymentIdempotency::REQUEST_ATTRIBUTE => trim((string) $this->header(PaymentIdempotency::REQUEST_HEADER))]);
    }

    public function context(): AgreementContext
    {
        return new AgreementContext($this->tenantId(), (int) $this->organizationUnitId(), (int) $this->currentUserId());
    }
}
