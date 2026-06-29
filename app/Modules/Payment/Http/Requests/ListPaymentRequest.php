<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Payment\Enums\PaymentAllocationState;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentInstrumentStatus;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Enums\PaymentType;

final class ListPaymentRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:150'],
            'payment_type' => ['nullable', Rule::enum(PaymentType::class)],
            'direction' => ['nullable', Rule::enum(PaymentDirection::class)],
            'document_status' => ['nullable', Rule::enum(PaymentDocumentStatus::class)],
            'allocation_status' => ['nullable', Rule::enum(PaymentAllocationState::class)],
            'posting_status' => ['nullable', Rule::enum(PaymentPostingStatus::class)],
            'instrument_status' => ['nullable', Rule::enum(PaymentInstrumentStatus::class)],
            'party_id' => ['nullable', 'integer', 'min:1'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
