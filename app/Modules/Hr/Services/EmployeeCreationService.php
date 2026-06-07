<?php

declare(strict_types=1);

namespace Modules\Hr\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Hr\DTOs\CreateEmployeeData;
use Modules\Hr\Enums\EmployeeStatus;
use Modules\Hr\Models\HrEmployee;

final class EmployeeCreationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly EmployeeValidationService $validator,
        private readonly EmployeeNumberService $numbers,
        private readonly EmployeeContactService $contacts,
        private readonly EmployeeAddressService $addresses,
        private readonly EmployeeDocumentService $documents,
        private readonly EmployeeSkillService $skills,
        private readonly EmployeeCertificationService $certifications,
        private readonly EmployeeLicenseService $licenses,
        private readonly EmployeeRateService $rates,
        private readonly EmployeeAvailabilityService $availability,
        private readonly EmployeeStatusService $statuses,
    ) {}

    public function create(CreateEmployeeData $data): HrEmployee
    {
        $this->validator->validateCreate($data);

        return DB::transaction(function () use ($data): HrEmployee {
            $employee = HrEmployee::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'employee_number' => $data->employeeNumber ?? $this->numbers->next($data->tenantId),
                'code' => $data->code,
                'first_name' => $data->firstName,
                'middle_name' => $data->middleName,
                'last_name' => $data->lastName,
                'display_name' => $data->displayName,
                'email' => $data->email,
                'phone' => $data->phone,
                'mobile' => $data->mobile,
                'department_id' => $data->departmentId,
                'designation_id' => $data->designationId,
                'employment_type_id' => $data->employmentTypeId,
                'reporting_manager_id' => $data->reportingManagerId,
                'joined_date' => $data->joinedDate,
                'resigned_date' => $data->resignedDate,
                'date_of_birth' => $data->dateOfBirth,
                'gender' => $data->gender,
                'status' => $data->status,
                'availability_status' => $data->availabilityStatus,
                'default_hourly_rate' => $this->math->normalize($data->defaultHourlyRate),
                'default_daily_rate' => $this->math->normalize($data->defaultDailyRate),
                'default_service_rate' => $this->math->normalize($data->defaultServiceRate),
                'notes' => $data->notes,
                'metadata' => $data->metadata,
                'approved_by' => $data->status === EmployeeStatus::Active ? $data->createdBy : null,
                'approved_at' => $data->status === EmployeeStatus::Active ? now() : null,
            ]);

            foreach ($data->contacts as $row) { $this->contacts->create($employee, $row); }
            foreach ($data->addresses as $row) { $this->addresses->create($employee, $row); }
            foreach ($data->documents as $row) { $this->documents->create($employee, $row); }
            foreach ($data->skills as $row) { $this->skills->create($employee, $row); }
            foreach ($data->certifications as $row) { $this->certifications->create($employee, $row); }
            foreach ($data->licenses as $row) { $this->licenses->create($employee, $row); }
            foreach ($data->rates as $row) { $this->rates->create($employee, $row); }
            if ($data->availability !== null) { $this->availability->create($employee, $data->availability); }
            $this->statuses->recordInitial($employee, $data->createdBy);

            return $employee->refresh()->load($this->relations());
        });
    }

    private function relations(): array
    {
        return ['department', 'designation', 'employmentType', 'reportingManager', 'contacts', 'addresses', 'documents', 'skillAssignments.skill', 'certificationAssignments.certification', 'licenseAssignments.license', 'rates.currency', 'availabilities', 'statusHistories'];
    }
}
