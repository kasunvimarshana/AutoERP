<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleService\DTOs\VehicleServiceEmployeeAssignmentBatchEntryData;
use Modules\VehicleService\DTOs\VehicleServiceEmployeeAssignmentData;
use Modules\VehicleService\Http\Requests\Concerns\HasExpectedVehicleServiceJobVersion;

final class StoreVehicleServiceEmployeeBatchRequest extends TenantScopedRequest
{
    use HasExpectedVehicleServiceJobVersion;

    private const MAX_ASSIGNMENTS_PER_REQUEST = 100;

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'expected_version' => $this->expectedVersionRules(),
            'assignments' => ['required', 'array', 'min:1', 'max:'.self::MAX_ASSIGNMENTS_PER_REQUEST],
            'assignments.*.line_id' => ['required', 'integer', 'min:1', 'distinct'],
            'assignments.*.employee_id' => ['required', 'integer', 'min:1'],
        ];
    }

    /** @return list<VehicleServiceEmployeeAssignmentBatchEntryData> */
    public function toData(): array
    {
        return array_map(
            static fn (array $row): VehicleServiceEmployeeAssignmentBatchEntryData => new VehicleServiceEmployeeAssignmentBatchEntryData(
                lineId: (int) $row['line_id'],
                assignment: new VehicleServiceEmployeeAssignmentData(employeeId: (int) $row['employee_id']),
            ),
            $this->validated('assignments'),
        );
    }
}
