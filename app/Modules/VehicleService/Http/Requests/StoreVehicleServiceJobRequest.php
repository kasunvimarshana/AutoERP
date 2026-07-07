<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleService\DTOs\VehicleServiceJobData;
use Modules\VehicleService\Enums\VehicleServiceCommissionType;
use Modules\VehicleService\Http\Requests\Concerns\HasExpectedVehicleServiceJobVersion;

final class StoreVehicleServiceJobRequest extends TenantScopedRequest
{
    use HasExpectedVehicleServiceJobVersion;

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'expected_version' => $this->expectedVersionRules(! $this->isMethod('post')),
            'job_number' => ['nullable', 'string', 'max:100'],
            'job_date' => ['required', 'date'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:job_date'],
            'customer_id' => ['required', 'integer', 'min:1'],
            'vehicle_id' => ['required', 'integer', 'min:1'],
            'bill_to_customer_id' => ['nullable', 'integer', 'min:1'],
            'supervisor_employee_id' => ['nullable', 'integer', 'min:1'],
            'supervisor_commission_type' => ['nullable', Rule::enum(VehicleServiceCommissionType::class)],
            'supervisor_commission_value' => ['nullable', 'decimal:0,6', 'min:0'],
            'odometer_reading' => ['nullable', 'decimal:0,6', 'min:0'],
            'fuel_level' => ['nullable', 'string', 'max:100'],
            'priority' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],
            'customer_complaint' => ['nullable', 'string'],
        ];
    }

    public function toData(): VehicleServiceJobData
    {
        return new VehicleServiceJobData(
            tenantId: $this->tenantId(),
            jobDate: (string) $this->input('job_date'),
            customerId: (int) $this->input('customer_id'),
            vehicleId: (int) $this->input('vehicle_id'),
            billToCustomerId: $this->intOrNull('bill_to_customer_id'),
            organizationUnitId: $this->organizationUnitId(),
            jobNumber: $this->stringOrNull('job_number'),
            expectedDeliveryDate: $this->stringOrNull('expected_delivery_date'),
            supervisorEmployeeId: $this->intOrNull('supervisor_employee_id'),
            supervisorCommissionType: VehicleServiceCommissionType::from((string) $this->input('supervisor_commission_type', 'none')),
            supervisorCommissionValue: (string) $this->input('supervisor_commission_value', '0.000000'),
            odometerReading: $this->stringOrNull('odometer_reading'),
            fuelLevel: $this->stringOrNull('fuel_level'),
            priority: $this->stringOrNull('priority'),
            notes: $this->stringOrNull('notes'),
            customerComplaint: $this->stringOrNull('customer_complaint'),
            customerComplaintProvided: $this->has('customer_complaint'),
            createdBy: $this->currentUserId(),
        );
    }

    private function intOrNull(string $key): ?int
    {
        return $this->filled($key) ? (int) $this->input($key) : null;
    }

    private function stringOrNull(string $key): ?string
    {
        return $this->filled($key) ? (string) $this->input($key) : null;
    }
}
