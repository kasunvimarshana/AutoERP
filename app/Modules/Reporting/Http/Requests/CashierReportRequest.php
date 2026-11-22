<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Reporting\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentDocumentStatus;

final class CashierReportRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['nullable', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'cashier_user_id' => ['nullable', 'integer', 'min:1'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'direction' => ['nullable', Rule::in(['all', PaymentDirection::Inbound->value, PaymentDirection::Outbound->value])],
            'document_status' => ['nullable', Rule::in([
                'all',
                PaymentDocumentStatus::Draft->value,
                PaymentDocumentStatus::Submitted->value,
                PaymentDocumentStatus::Approved->value,
                PaymentDocumentStatus::Rejected->value,
                PaymentDocumentStatus::Voided->value,
                PaymentDocumentStatus::Reversed->value,
            ])],
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
