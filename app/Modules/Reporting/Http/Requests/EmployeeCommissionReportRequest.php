<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Payment\Enums\PaymentAllocationState;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentInstrumentStatus;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\VehicleService\Enums\VehicleServiceBillingStatus;
use Modules\VehicleService\Enums\VehicleServiceCommissionType;
use Modules\VehicleService\Enums\VehicleServiceOperationalStatus;
use Modules\VehicleService\Enums\VehicleServicePaymentStatus;

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
            'group_by' => ['nullable', Rule::in(['employee', 'department', 'designation', 'supervisor', 'commission_source'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'employee_id' => ['nullable', 'integer', 'min:1'],
            'department_id' => ['nullable', 'integer', 'min:1'],
            'designation_id' => ['nullable', 'integer', 'min:1'],
            'supervisor_id' => ['nullable', 'integer', 'min:1'],
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'vehicle_id' => ['nullable', 'integer', 'min:1'],
            'operational_status' => ['nullable', Rule::enum(VehicleServiceOperationalStatus::class)],
            'billing_status' => ['nullable', Rule::enum(VehicleServiceBillingStatus::class)],
            'payment_status' => ['nullable', Rule::enum(VehicleServicePaymentStatus::class)],
            'invoice_status' => ['nullable', Rule::enum(InvoiceStatus::class)],
            'payment_document_status' => ['nullable', Rule::enum(PaymentDocumentStatus::class)],
            'payment_posting_status' => ['nullable', Rule::enum(PaymentPostingStatus::class)],
            'payment_allocation_status' => ['nullable', Rule::enum(PaymentAllocationState::class)],
            'payment_instrument_status' => ['nullable', Rule::enum(PaymentInstrumentStatus::class)],
            'commission_type' => ['nullable', Rule::enum(VehicleServiceCommissionType::class)],
            'commission_source' => ['nullable', Rule::in(['technician', 'supervisor'])],
            'role_type' => ['nullable', Rule::in(['technician', 'helper', 'inspector', 'custom', 'supervisor'])],
            'commission_status' => ['nullable', Rule::in(['pending', 'earned', 'cancelled'])],
            'include_cancelled' => ['nullable', 'boolean'],
        ];
    }
}
