<?php
declare(strict_types=1);
namespace Modules\HR\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HR\Domain\Entities\Employee;

class EmployeeResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var Employee $emp */
        $emp = $this->resource;
        return [
            'id'                       => $emp->getId(),
            'tenant_id'                => $emp->getTenantId(),
            'user_id'                  => $emp->getUserId(),
            'department_id'            => $emp->getDepartmentId(),
            'position_id'              => $emp->getPositionId(),
            'employee_code'            => $emp->getEmployeeCode(),
            'first_name'               => $emp->getFirstName(),
            'last_name'                => $emp->getLastName(),
            'full_name'                => $emp->getFullName(),
            'email'                    => $emp->getEmail(),
            'phone'                    => $emp->getPhone(),
            'gender'                   => $emp->getGender(),
            'date_of_birth'            => $emp->getDateOfBirth()?->format('Y-m-d'),
            'hire_date'                => $emp->getHireDate()->format('Y-m-d'),
            'termination_date'         => $emp->getTerminationDate()?->format('Y-m-d'),
            'status'                   => $emp->getStatus(),
            'base_salary'              => $emp->getBaseSalary(),
            'bank_account'             => $emp->getBankAccount(),
            'tax_id'                   => $emp->getTaxId(),
            'address'                  => $emp->getAddress(),
            'emergency_contact_name'   => $emp->getEmergencyContactName(),
            'emergency_contact_phone'  => $emp->getEmergencyContactPhone(),
            'metadata'                 => $emp->getMetadata(),
            'created_at'               => $emp->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at'               => $emp->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
