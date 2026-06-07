<?php
declare(strict_types=1);
namespace Modules\Hr\Services;
use Modules\Hr\DTOs\EmployeeAvailabilityData;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Models\HrEmployeeAvailability;
final class EmployeeAvailabilityService
{
    public function __construct(private readonly EmployeeValidationService $validator) {}
    public function create(HrEmployee $employee, EmployeeAvailabilityData $data): HrEmployeeAvailability { $this->validator->assertDateRange($data->startsAt, $data->endsAt); $row = $employee->availabilities()->create(['tenant_id' => $employee->tenant_id, 'organization_unit_id' => $employee->organization_unit_id, 'availability_date' => $data->availabilityDate, 'availability_status' => $data->availabilityStatus, 'source_type' => $data->sourceType, 'source_id' => $data->sourceId, 'reason' => $data->reason, 'starts_at' => $data->startsAt, 'ends_at' => $data->endsAt]); $employee->availability_status = $data->availabilityStatus; $employee->save(); return $row; }
    public function updateCurrent(HrEmployee $employee, EmployeeAvailabilityData $data): HrEmployee { $this->create($employee, $data); return $employee->refresh(); }
}
