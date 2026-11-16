<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\ValidationException;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Payment\Constants\PaymentIdempotency;
use Modules\Payment\Http\Requests\Concerns\BuildsPaymentLines;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AgreementKind;

final class RentalPaymentRequest extends TenantScopedRequest
{
    use BuildsPaymentLines;

    private const PAYMENT_REFERENCE_LENGTH = 150;

    public function rules(): array
    {
        return [
            'expected_version' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'payment_date' => ['required', 'date_format:'.AgreementFields::DATE_FORMAT],
            'exchange_rate' => ['sometimes', 'nullable', 'regex:'.AgreementFields::DECIMAL_PATTERN, 'gt:0'],
            'reference_number' => ['nullable', 'string', 'max:'.self::PAYMENT_REFERENCE_LENGTH],
            'notes' => ['nullable', 'string', 'max:'.AgreementFields::NOTES_LENGTH],
            PaymentIdempotency::REQUEST_ATTRIBUTE => ['nullable', 'string', 'max:'.PaymentIdempotency::MAX_KEY_LENGTH],
            'idempotency_key' => ['nullable', 'string', 'max:255'],
            'allocations' => ['nullable', 'array'],
            'allocations.*.invoice_id' => ['required_with:allocations', 'integer', 'min:1'],
            'allocations.*.amount' => ['required_with:allocations', 'regex:'.AgreementFields::DECIMAL_PATTERN, 'gt:0'],
            'currency_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'agreement_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'agreement_reference' => ['sometimes', 'nullable', 'string', 'max:150'],
            ...$this->paymentLineRules(),
            'party_id' => ['prohibited'],
            'party_type' => ['prohibited'],
            'source_id' => ['prohibited'],
            'source_type' => ['prohibited'],
            'payment_type' => ['prohibited'],
            'direction' => ['prohibited'],
            'metadata' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $headerKey = trim((string) $this->header(PaymentIdempotency::REQUEST_HEADER));
        $bodyKey = trim((string) $this->input('idempotency_key', ''));
        $effectiveKey = $headerKey !== '' ? $headerKey : ($bodyKey !== '' ? $bodyKey : null);

        $merges = [
            PaymentIdempotency::REQUEST_ATTRIBUTE => $effectiveKey,
            'idempotency_key' => $effectiveKey,
        ];

        foreach (['reference_number', 'notes', 'exchange_rate', 'currency_id', 'agreement_id', 'agreement_reference'] as $field) {
            if ($this->has($field) && is_string($this->input($field)) && trim($this->input($field)) === '') {
                $merges[$field] = null;
            }
        }

        $this->merge($merges);
    }

    public function context(): AgreementContext
    {
        if ($this->organizationUnitId() === null || $this->currentUserId() === null) {
            throw ValidationException::withMessages(['context' => ['Select an organization and sign in before managing rental payments.']]);
        }

        return new AgreementContext($this->tenantId(), (int) $this->organizationUnitId(), (int) $this->currentUserId());
    }

    public function kind(): AgreementKind
    {
        $rawKind = (string) $this->route('kind');
        $kind = AgreementKind::tryFrom($rawKind);
        if ($kind === null) {
            throw ValidationException::withMessages(['kind' => ["Invalid agreement kind: {$rawKind}"]]);
        }

        return $kind;
    }

    public function allocationsInput(): array
    {
        $allocations = $this->input('allocations');
        if (! is_array($allocations)) {
            return [];
        }

        return array_values(array_filter($allocations, static fn ($a) => is_array($a) && isset($a['invoice_id'], $a['amount'])));
    }
}
