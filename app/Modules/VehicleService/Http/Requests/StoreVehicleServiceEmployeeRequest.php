<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleService\DTOs\VehicleServiceEmployeeAssignmentData;
use Modules\VehicleService\Enums\VehicleServiceCommissionType;
use Modules\VehicleService\Http\Requests\Concerns\HasExpectedVehicleServiceJobVersion;

final class StoreVehicleServiceEmployeeRequest extends TenantScopedRequest
{
    use HasExpectedVehicleServiceJobVersion;

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'expected_version' => $this->expectedVersionRules(),
            'employee_id' => ['required', 'integer', 'min:1'],
            'role_type' => ['required', Rule::in(['technician', 'helper', 'inspector', 'custom'])],
            'assigned_hours' => ['nullable', 'decimal:0,6', 'min:0'],
            'rate' => ['nullable', 'decimal:0,6', 'min:0'],
            'commission_type' => ['nullable', Rule::enum(VehicleServiceCommissionType::class)],
            'commission_value' => ['nullable', 'decimal:0,6', 'min:0'],
            'status' => ['nullable', Rule::in(['assigned', 'completed', 'cancelled'])],
        ];
    }

    public function toData(): VehicleServiceEmployeeAssignmentData
    {
        return new VehicleServiceEmployeeAssignmentData(
            employeeId: (int) $this->input('employee_id'),
            roleType: (string) $this->input('role_type'),
            assignedHours: (string) $this->input('assigned_hours', '0.000000'),
            rate: (string) $this->input('rate', '0.000000'),
            commissionType: VehicleServiceCommissionType::from((string) $this->input('commission_type', 'none')),
            commissionValue: (string) $this->input('commission_value', '0.000000'),
            status: (string) $this->input('status', 'assigned'),
        );
    }
}
