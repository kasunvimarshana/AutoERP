<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\VehicleRental\Enums\RentalAssignmentStatus;
use Modules\VehicleRental\Enums\RentalRunningChartStatus;

final class VehicleRentalReportRequest extends TenantScopedRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['required', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
            'search' => ['nullable', 'string', 'max:150'],
            'sort' => ['nullable', 'string', 'max:80'],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'chart_status' => ['nullable', Rule::enum(RentalRunningChartStatus::class)],
            'assignment_status' => ['nullable', Rule::enum(RentalAssignmentStatus::class)],
            'invoice_status' => ['nullable', Rule::enum(InvoiceStatus::class)],
            'exception_type' => ['nullable', Rule::in([
                'missing_chart',
                'duplicate_assignment_date',
                'duplicate_vehicle_date',
            ])],
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'supplier_id' => ['nullable', 'integer', 'min:1'],
            'vehicle_id' => ['nullable', 'integer', 'min:1'],
            'agreement_id' => ['nullable', 'integer', 'min:1'],
            'driver_employee_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
