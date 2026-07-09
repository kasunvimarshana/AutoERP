<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\VehicleService\Enums\VehicleServiceBillingStatus;
use Modules\VehicleService\Enums\VehicleServiceLineSourceType;
use Modules\VehicleService\Enums\VehicleServiceOperationalStatus;
use Modules\VehicleService\Enums\VehicleServicePaymentStatus;

final class OperationalReportRequest extends TenantScopedRequest
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
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'purchase_status' => ['nullable', Rule::enum(PurchaseOrderStatus::class)],
            'operational_status' => ['nullable', Rule::enum(VehicleServiceOperationalStatus::class)],
            'billing_status' => ['nullable', Rule::enum(VehicleServiceBillingStatus::class)],
            'payment_status' => ['nullable', Rule::enum(VehicleServicePaymentStatus::class)],
            'line_source_type' => ['nullable', Rule::enum(VehicleServiceLineSourceType::class)],
            'incentive_source' => ['nullable', Rule::in(['technician', 'supervisor'])],
            'supplier_id' => ['nullable', 'integer', 'min:1'],
            'item_id' => ['nullable', 'integer', 'min:1'],
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'vehicle_id' => ['nullable', 'integer', 'min:1'],
            'employee_id' => ['nullable', 'integer', 'min:1'],
            'department_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
