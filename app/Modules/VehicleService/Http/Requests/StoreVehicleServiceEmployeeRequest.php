<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleService\DTOs\VehicleServiceEmployeeAssignmentData;
use Modules\VehicleService\Enums\VehicleServiceCommissionType;
use Modules\VehicleService\Enums\VehicleServiceWorkforceRole;
use Modules\VehicleService\Http\Requests\Concerns\HasExpectedVehicleServiceJobVersion;

final class StoreVehicleServiceEmployeeRequest extends TenantScopedRequest
{
    use HasExpectedVehicleServiceJobVersion;

    private const DEFAULT_DECIMAL_VALUE = '0.000000';
    private const DEFAULT_STATUS = 'assigned';

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'expected_version' => $this->expectedVersionRules(),
            'employee_id' => ['required', 'integer', 'min:1'],
            'role_type' => ['required', Rule::enum(VehicleServiceWorkforceRole::class)],
            'assigned_hours' => ['nullable', 'decimal:0,6', 'min:0'],
            'rate' => ['nullable', 'decimal:0,6', 'min:0'],
            'commission_type' => [
                'nullable',
                'required_with:commission_value',
                Rule::enum(VehicleServiceCommissionType::class),
            ],
            'commission_value' => [
                'nullable',
                'required_with:commission_type',
                'decimal:0,6',
                'min:0',
            ],
            'status' => ['nullable', Rule::in(['assigned', 'completed', 'cancelled'])],
        ];
    }

    public function toData(): VehicleServiceEmployeeAssignmentData
    {
        return new VehicleServiceEmployeeAssignmentData(
            employeeId: (int) $this->input('employee_id'),
            roleType: (string) $this->input('role_type'),
            assignedHours: $this->stringOrNull('assigned_hours') ?? self::DEFAULT_DECIMAL_VALUE,
            rate: $this->stringOrNull('rate') ?? self::DEFAULT_DECIMAL_VALUE,
            commissionType: $this->has('commission_type')
                ? VehicleServiceCommissionType::from((string) $this->input('commission_type'))
                : null,
            commissionValue: $this->has('commission_value')
                ? (string) $this->input('commission_value')
                : null,
            status: $this->stringOrNull('status') ?? self::DEFAULT_STATUS,
        );
    }

    private function stringOrNull(string $key): ?string
    {
        return $this->filled($key) ? (string) $this->input($key) : null;
    }
}
