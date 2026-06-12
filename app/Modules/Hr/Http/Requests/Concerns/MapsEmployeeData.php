<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Requests\Concerns;

use Modules\Hr\DTOs\CreateEmployeeData;
use Modules\Hr\DTOs\EmployeeAddressData;
use Modules\Hr\DTOs\EmployeeAvailabilityData;
use Modules\Hr\DTOs\EmployeeCertificationAssignmentData;
use Modules\Hr\DTOs\EmployeeContactData;
use Modules\Hr\DTOs\EmployeeDocumentData;
use Modules\Hr\DTOs\EmployeeLicenseAssignmentData;
use Modules\Hr\DTOs\EmployeeRateData;
use Modules\Hr\DTOs\EmployeeSkillAssignmentData;
use Modules\Hr\Enums\EmployeeAddressType;
use Modules\Hr\Enums\EmployeeAvailabilityStatus;
use Modules\Hr\Enums\EmployeeDocumentStatus;
use Modules\Hr\Enums\EmployeeDocumentType;
use Modules\Hr\Enums\EmployeeRateType;
use Modules\Hr\Enums\EmployeeStatus;
use Modules\Hr\Enums\Gender;
use Modules\Hr\Enums\SkillProficiencyLevel;

trait MapsEmployeeData
{
    private function mapEmployeeData(array $employee, array $relations = []): CreateEmployeeData
    {
        return new CreateEmployeeData(
            tenantId: $this->tenantId(),
            firstName: (string) $employee['first_name'],
            displayName: (string) ($employee['display_name'] ?? trim(implode(' ', array_filter([$employee['first_name'] ?? null, $employee['middle_name'] ?? null, $employee['last_name'] ?? null])))),
            organizationUnitId: $this->organizationUnitId(),
            employeeNumber: $this->nullableString($employee, 'employee_number'),
            code: $this->nullableString($employee, 'code'),
            middleName: $this->nullableString($employee, 'middle_name'),
            lastName: $this->nullableString($employee, 'last_name'),
            email: $this->nullableString($employee, 'email'),
            phone: $this->nullableString($employee, 'phone'),
            mobile: $this->nullableString($employee, 'mobile'),
            departmentId: $this->nullableInteger($employee, 'department_id'),
            designationId: $this->nullableInteger($employee, 'designation_id'),
            employmentTypeId: $this->nullableInteger($employee, 'employment_type_id'),
            reportingManagerId: $this->nullableInteger($employee, 'reporting_manager_id'),
            joinedDate: $this->nullableString($employee, 'joined_date'),
            resignedDate: $this->nullableString($employee, 'resigned_date'),
            dateOfBirth: $this->nullableString($employee, 'date_of_birth'),
            gender: isset($employee['gender']) && $employee['gender'] !== '' ? Gender::from((string) $employee['gender']) : null,
            status: EmployeeStatus::from((string) ($employee['status'] ?? EmployeeStatus::PendingApproval->value)),
            availabilityStatus: EmployeeAvailabilityStatus::from((string) ($employee['availability_status'] ?? EmployeeAvailabilityStatus::Available->value)),
            defaultHourlyRate: (string) ($employee['default_hourly_rate'] ?? '0.000000'),
            defaultDailyRate: (string) ($employee['default_daily_rate'] ?? '0.000000'),
            defaultServiceRate: (string) ($employee['default_service_rate'] ?? '0.000000'),
            notes: $this->nullableString($employee, 'notes'),
            metadata: $employee['metadata'] ?? null,
            createdBy: $this->currentUserId(),
            contacts: array_map(fn (array $row) => $this->contactData($row), $relations['contacts'] ?? []),
            addresses: array_map(fn (array $row) => $this->addressData($row), $relations['addresses'] ?? []),
            documents: array_map(fn (array $row) => $this->documentData($row), $relations['documents'] ?? []),
            skills: array_map(fn (array $row) => $this->skillData($row), $relations['skills'] ?? []),
            certifications: array_map(fn (array $row) => $this->certificationData($row), $relations['certifications'] ?? []),
            licenses: array_map(fn (array $row) => $this->licenseData($row), $relations['licenses'] ?? []),
            rates: array_map(fn (array $row) => $this->rateData($row), $relations['rates'] ?? []),
            availability: isset($relations['availability']) && is_array($relations['availability']) ? $this->availabilityData($relations['availability']) : null,
        );
    }

    public function contactData(array $row): EmployeeContactData { return new EmployeeContactData((string) $row['contact_name'], $row['relationship'] ?? null, $row['email'] ?? null, $row['phone'] ?? null, $row['mobile'] ?? null, (bool) ($row['is_emergency_contact'] ?? false), (bool) ($row['is_primary'] ?? false), (bool) ($row['is_active'] ?? true), $row['notes'] ?? null); }
    public function addressData(array $row): EmployeeAddressData { return new EmployeeAddressData(EmployeeAddressType::from((string) $row['address_type']), (string) $row['address_line_1'], $row['address_line_2'] ?? null, $row['city'] ?? null, $row['state'] ?? null, $row['postal_code'] ?? null, $row['country'] ?? null, (bool) ($row['is_primary'] ?? false), (bool) ($row['is_active'] ?? true)); }
    public function documentData(array $row): EmployeeDocumentData { return new EmployeeDocumentData(EmployeeDocumentType::from((string) $row['document_type']), $row['document_number'] ?? null, $row['issued_date'] ?? null, $row['expiry_date'] ?? null, null, EmployeeDocumentStatus::from((string) ($row['status'] ?? 'pending')), $row['notes'] ?? null); }
    public function skillData(array $row): EmployeeSkillAssignmentData { return new EmployeeSkillAssignmentData((int) $row['skill_id'], SkillProficiencyLevel::from((string) ($row['proficiency_level'] ?? 'beginner')), (string) ($row['years_of_experience'] ?? '0.000000'), (bool) ($row['is_primary'] ?? false)); }
    public function certificationData(array $row): EmployeeCertificationAssignmentData { return new EmployeeCertificationAssignmentData((int) $row['certification_id'], $row['certificate_number'] ?? null, $row['issued_date'] ?? null, $row['expiry_date'] ?? null, EmployeeDocumentStatus::from((string) ($row['status'] ?? 'pending'))); }
    public function licenseData(array $row): EmployeeLicenseAssignmentData { return new EmployeeLicenseAssignmentData((int) $row['license_id'], $row['license_number'] ?? null, $row['issued_date'] ?? null, $row['expiry_date'] ?? null, EmployeeDocumentStatus::from((string) ($row['status'] ?? 'pending'))); }
    public function rateData(array $row): EmployeeRateData { return new EmployeeRateData(EmployeeRateType::from((string) $row['rate_type']), (string) $row['amount'], isset($row['currency_id']) ? (int) $row['currency_id'] : null, $row['effective_from'] ?? null, $row['effective_to'] ?? null, (bool) ($row['is_active'] ?? true)); }
    public function availabilityData(array $row): EmployeeAvailabilityData { return new EmployeeAvailabilityData(EmployeeAvailabilityStatus::from((string) $row['availability_status']), $row['availability_date'] ?? null, $row['source_type'] ?? null, isset($row['source_id']) ? (int) $row['source_id'] : null, $row['reason'] ?? null, $row['starts_at'] ?? null, $row['ends_at'] ?? null); }
    private function nullableString(array $data, string $key): ?string { return isset($data[$key]) && trim((string) $data[$key]) !== '' ? (string) $data[$key] : null; }
    private function nullableInteger(array $data, string $key): ?int { return isset($data[$key]) && $data[$key] !== '' ? (int) $data[$key] : null; }
}
