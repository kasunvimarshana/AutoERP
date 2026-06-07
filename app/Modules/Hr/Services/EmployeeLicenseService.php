<?php
declare(strict_types=1);
namespace Modules\Hr\Services;
use InvalidArgumentException;
use Modules\Hr\DTOs\EmployeeLicenseAssignmentData;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Models\HrEmployeeLicenseAssignment;
use Modules\Hr\Models\HrLicense;
final class EmployeeLicenseService
{
    public function __construct(private readonly EmployeeValidationService $validator) {}
    public function create(HrEmployee $employee, EmployeeLicenseAssignmentData $data): HrEmployeeLicenseAssignment { $this->validate($employee, $data); if ($employee->licenseAssignments()->where('license_id', $data->licenseId)->exists()) { throw new InvalidArgumentException('License is already assigned to the employee.'); } return $employee->licenseAssignments()->create($this->attributes($employee, $data)); }
    public function update(HrEmployee $employee, HrEmployeeLicenseAssignment $row, EmployeeLicenseAssignmentData $data): HrEmployeeLicenseAssignment { $this->owned($employee, $row); $this->validate($employee, $data); if ($employee->licenseAssignments()->whereKeyNot($row->getKey())->where('license_id', $data->licenseId)->exists()) { throw new InvalidArgumentException('License is already assigned to the employee.'); } $row->fill($this->attributes($employee, $data, false))->save(); return $row->refresh()->load('license'); }
    public function delete(HrEmployee $employee, HrEmployeeLicenseAssignment $row): void { $this->owned($employee, $row); $row->delete(); }
    public function replace(HrEmployee $employee, array $rows): void { $employee->licenseAssignments()->delete(); foreach ($rows as $row) { $this->create($employee, $row); } }
    private function validate(HrEmployee $employee, EmployeeLicenseAssignmentData $data): void { $master = HrLicense::query()->findOrFail($data->licenseId); $this->validator->assertScopedActive($master, $employee, 'license'); $this->validator->assertDateRange($data->issuedDate, $data->expiryDate); }
    private function attributes(HrEmployee $employee, EmployeeLicenseAssignmentData $data, bool $scope = true): array { return [...($scope ? ['tenant_id' => $employee->tenant_id, 'organization_unit_id' => $employee->organization_unit_id] : []), 'license_id' => $data->licenseId, 'license_number' => $data->licenseNumber, 'issued_date' => $data->issuedDate, 'expiry_date' => $data->expiryDate, 'status' => $data->status]; }
    private function owned(HrEmployee $employee, HrEmployeeLicenseAssignment $row): void { if ((int) $row->employee_id !== (int) $employee->getKey()) { throw new InvalidArgumentException('Employee license does not belong to the employee.'); } }
}
