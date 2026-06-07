<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Payment\Enums\PaymentStatus;
use Modules\VehicleService\Enums\VehicleServiceCommissionType;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;

final class EmployeeCommissionReportRequest extends TenantScopedRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
            'search' => ['nullable', 'string', 'max:150'],
            'sort' => ['nullable', 'string', 'max:80'],
            'direction' => ['nullable', 'in:asc,desc'],
            'group_by' => ['nullable', Rule::in(['employee', 'department', 'designation', 'supervisor'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'employee_id' => ['nullable', 'integer', 'min:1'],
            'department_id' => ['nullable', 'integer', 'min:1'],
            'designation_id' => ['nullable', 'integer', 'min:1'],
            'supervisor_id' => ['nullable', 'integer', 'min:1'],
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'vehicle_id' => ['nullable', 'integer', 'min:1'],
            'job_status' => ['nullable', Rule::enum(VehicleServiceJobStatus::class)],
            'invoice_status' => ['nullable', Rule::enum(InvoiceStatus::class)],
            'payment_status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'commission_type' => ['nullable', Rule::enum(VehicleServiceCommissionType::class)],
        ];
    }
}
