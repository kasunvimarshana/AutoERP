<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleService\DTOs\VehicleServiceJobData;
use Modules\VehicleService\Enums\VehicleServiceCommissionType;
use Modules\VehicleService\Enums\VehicleServiceJobType;
use Modules\VehicleService\Http\Requests\Concerns\HasExpectedVehicleServiceJobVersion;

final class StoreVehicleServiceJobRequest extends TenantScopedRequest
{
    use HasExpectedVehicleServiceJobVersion;

    public function rules(): array
    {
        $jobType = VehicleServiceJobType::tryFrom((string) $this->input('type'));
        $tracksMileage = $jobType?->tracksMileage() ?? false;

        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'expected_version' => $this->expectedVersionRules(! $this->isMethod('post')),
            'job_number' => ['nullable', 'string', 'max:100'],
            'job_date' => ['required', 'date'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:job_date'],
            'type' => ['required', Rule::enum(VehicleServiceJobType::class)],
            'customer_id' => ['required', 'integer', 'min:1'],
            'vehicle_id' => ['required', 'integer', 'min:1'],
            'bill_to_customer_id' => ['nullable', 'integer', 'min:1'],
            'supervisor_employee_id' => ['nullable', 'integer', 'min:1'],
            'supervisor_commission_type' => [
                'nullable',
                'required_with:supervisor_commission_value',
                Rule::enum(VehicleServiceCommissionType::class),
            ],
            'supervisor_commission_value' => [
                'nullable',
                'required_with:supervisor_commission_type',
                'decimal:0,6',
                'min:0',
            ],
            'odometer_reading' => [
                'nullable',
                Rule::requiredIf($tracksMileage),
                Rule::prohibitedIf(! $tracksMileage),
                'decimal:0,6',
                'min:0',
            ],
            'next_service_mileage' => [
                'nullable',
                Rule::prohibitedIf(! $tracksMileage),
                'decimal:0,6',
                'min:0',
            ],
            'manual_job_card' => ['nullable', 'string', 'max:100'],
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
            type: VehicleServiceJobType::from((string) $this->input('type')),
            billToCustomerId: $this->intOrNull('bill_to_customer_id'),
            organizationUnitId: $this->organizationUnitId(),
            jobNumber: $this->stringOrNull('job_number'),
            expectedDeliveryDate: $this->stringOrNull('expected_delivery_date'),
            supervisorEmployeeId: $this->intOrNull('supervisor_employee_id'),
            supervisorCommissionType: $this->filled('supervisor_commission_type')
                ? VehicleServiceCommissionType::from((string) $this->input('supervisor_commission_type'))
                : null,
            supervisorCommissionValue: $this->filled('supervisor_commission_value')
                ? (string) $this->input('supervisor_commission_value')
                : null,
            odometerReading: $this->stringOrNull('odometer_reading'),
            nextServiceMileage: $this->stringOrNull('next_service_mileage'),
            manualJobCard: $this->stringOrNull('manual_job_card'),
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
