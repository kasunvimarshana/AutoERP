<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Reporting\Services\PaymentMethodWiseSummaryReportService;

final class PaymentMethodWiseSummaryReportRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'source' => ['nullable', Rule::in([
                PaymentMethodWiseSummaryReportService::SOURCE_ALL,
                PaymentMethodWiseSummaryReportService::SOURCE_SELLING,
                PaymentMethodWiseSummaryReportService::SOURCE_SERVICE,
            ])],
        ];
    }
}
