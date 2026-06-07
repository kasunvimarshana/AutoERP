<?php
declare(strict_types=1);
namespace Modules\Hr\Services;
use InvalidArgumentException;
use Modules\Hr\DTOs\EmployeeCertificationAssignmentData;
use Modules\Hr\Models\HrCertification;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Models\HrEmployeeCertificationAssignment;
final class EmployeeCertificationService
{
    public function __construct(private readonly EmployeeValidationService $validator) {}
    public function create(HrEmployee $employee, EmployeeCertificationAssignmentData $data): HrEmployeeCertificationAssignment { $this->validate($employee, $data); if ($employee->certificationAssignments()->where('certification_id', $data->certificationId)->exists()) { throw new InvalidArgumentException('Certification is already assigned to the employee.'); } return $employee->certificationAssignments()->create($this->attributes($employee, $data)); }
    public function update(HrEmployee $employee, HrEmployeeCertificationAssignment $row, EmployeeCertificationAssignmentData $data): HrEmployeeCertificationAssignment { $this->owned($employee, $row); $this->validate($employee, $data); if ($employee->certificationAssignments()->whereKeyNot($row->getKey())->where('certification_id', $data->certificationId)->exists()) { throw new InvalidArgumentException('Certification is already assigned to the employee.'); } $row->fill($this->attributes($employee, $data, false))->save(); return $row->refresh()->load('certification'); }
    public function delete(HrEmployee $employee, HrEmployeeCertificationAssignment $row): void { $this->owned($employee, $row); $row->delete(); }
    public function replace(HrEmployee $employee, array $rows): void { $employee->certificationAssignments()->delete(); foreach ($rows as $row) { $this->create($employee, $row); } }
    private function validate(HrEmployee $employee, EmployeeCertificationAssignmentData $data): void { $master = HrCertification::query()->findOrFail($data->certificationId); $this->validator->assertScopedActive($master, $employee, 'certification'); $this->validator->assertDateRange($data->issuedDate, $data->expiryDate); }
    private function attributes(HrEmployee $employee, EmployeeCertificationAssignmentData $data, bool $scope = true): array { return [...($scope ? ['tenant_id' => $employee->tenant_id, 'organization_unit_id' => $employee->organization_unit_id] : []), 'certification_id' => $data->certificationId, 'certificate_number' => $data->certificateNumber, 'issued_date' => $data->issuedDate, 'expiry_date' => $data->expiryDate, 'status' => $data->status]; }
    private function owned(HrEmployee $employee, HrEmployeeCertificationAssignment $row): void { if ((int) $row->employee_id !== (int) $employee->getKey()) { throw new InvalidArgumentException('Employee certification does not belong to the employee.'); } }
}
