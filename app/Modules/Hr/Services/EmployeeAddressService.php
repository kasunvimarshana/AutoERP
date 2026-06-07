<?php
declare(strict_types=1);
namespace Modules\Hr\Services;
use InvalidArgumentException;
use Modules\Hr\DTOs\EmployeeAddressData;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Models\HrEmployeeAddress;
final class EmployeeAddressService
{
    public function create(HrEmployee $employee, EmployeeAddressData $data): HrEmployeeAddress { $this->validate($employee, $data); return $employee->addresses()->create($this->attributes($employee, $data)); }
    public function update(HrEmployee $employee, HrEmployeeAddress $address, EmployeeAddressData $data): HrEmployeeAddress { $this->owned($employee, $address); $this->validate($employee, $data, (int) $address->getKey()); $address->fill($this->attributes($employee, $data, false))->save(); return $address->refresh(); }
    public function delete(HrEmployee $employee, HrEmployeeAddress $address): void { $this->owned($employee, $address); $address->delete(); }
    public function replace(HrEmployee $employee, array $rows): void { $employee->addresses()->delete(); foreach ($rows as $row) { $this->create($employee, $row); } }
    private function validate(HrEmployee $employee, EmployeeAddressData $data, ?int $ignore = null): void { if (trim($data->addressLine1) === '') { throw new InvalidArgumentException('Employee address line 1 is required.'); } if ($data->isPrimary) { $query = $employee->addresses()->where('is_primary', true); if ($ignore !== null) { $query->whereKeyNot($ignore); } if ($query->exists()) { throw new InvalidArgumentException('Employee can have only one primary address.'); } } }
    private function attributes(HrEmployee $employee, EmployeeAddressData $data, bool $scope = true): array { return [...($scope ? ['tenant_id' => $employee->tenant_id, 'organization_unit_id' => $employee->organization_unit_id] : []), 'address_type' => $data->addressType, 'address_line_1' => $data->addressLine1, 'address_line_2' => $data->addressLine2, 'city' => $data->city, 'state' => $data->state, 'postal_code' => $data->postalCode, 'country' => $data->country, 'is_primary' => $data->isPrimary, 'is_active' => $data->isActive]; }
    private function owned(HrEmployee $employee, HrEmployeeAddress $row): void { if ((int) $row->employee_id !== (int) $employee->getKey()) { throw new InvalidArgumentException('Employee address does not belong to the employee.'); } }
}
