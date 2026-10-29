<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Requests;

use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Finance\Enums\ReceivableAgingBucket;

final class CustomerOutstandingReportRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'customer_id' => ['nullable', 'integer', 'min:1', $this->tenantExists('customers')],
            'location_id' => ['nullable', 'integer', 'min:1', $this->tenantExists('organization_units')],
            'as_of_date' => ['nullable', 'date', 'before_or_equal:'.$this->reportToday()],
            'timezone' => ['nullable', 'timezone'],
            'aging_bucket' => ['nullable', Rule::enum(ReceivableAgingBucket::class)],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }

    public function reportToday(): string
    {
        $timezone = $this->input('timezone');
        if (! is_string($timezone) || ! in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
            $timezone = (string) config('app.timezone');
        }

        return CarbonImmutable::now($timezone)->toDateString();
    }
}
