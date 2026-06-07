<?php
declare(strict_types=1);
namespace Modules\Hr\Services;
use InvalidArgumentException;
use Modules\Hr\DTOs\EmployeeContactData;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Models\HrEmployeeContact;
final class EmployeeContactService
{
    public function create(HrEmployee $employee, EmployeeContactData $data): HrEmployeeContact { $this->validate($employee, $data); return $employee->contacts()->create($this->attributes($employee, $data)); }
    public function update(HrEmployee $employee, HrEmployeeContact $contact, EmployeeContactData $data): HrEmployeeContact { $this->owned($employee, $contact); $this->validate($employee, $data, (int) $contact->getKey()); $contact->fill($this->attributes($employee, $data, false))->save(); return $contact->refresh(); }
    public function delete(HrEmployee $employee, HrEmployeeContact $contact): void { $this->owned($employee, $contact); $contact->delete(); }
    public function replace(HrEmployee $employee, array $rows): void { $employee->contacts()->delete(); foreach ($rows as $row) { $this->create($employee, $row); } }
    private function validate(HrEmployee $employee, EmployeeContactData $data, ?int $ignore = null): void { if (trim($data->contactName) === '') { throw new InvalidArgumentException('Employee contact name is required.'); } if ($data->isPrimary) { $query = $employee->contacts()->where('is_primary', true); if ($ignore !== null) { $query->whereKeyNot($ignore); } if ($query->exists()) { throw new InvalidArgumentException('Employee can have only one primary contact.'); } } }
    private function attributes(HrEmployee $employee, EmployeeContactData $data, bool $scope = true): array { return [...($scope ? ['tenant_id' => $employee->tenant_id, 'organization_unit_id' => $employee->organization_unit_id] : []), 'contact_name' => $data->contactName, 'relationship' => $data->relationship, 'email' => $data->email, 'phone' => $data->phone, 'mobile' => $data->mobile, 'is_emergency_contact' => $data->isEmergencyContact, 'is_primary' => $data->isPrimary, 'is_active' => $data->isActive, 'notes' => $data->notes]; }
    private function owned(HrEmployee $employee, HrEmployeeContact $row): void { if ((int) $row->employee_id !== (int) $employee->getKey()) { throw new InvalidArgumentException('Employee contact does not belong to the employee.'); } }
}
