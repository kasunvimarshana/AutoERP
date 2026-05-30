<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Application\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\HR\Application\Contracts\Services\HrEmployeeManagementServiceInterface;
use Modules\HR\Domain\Constants\EmployeeStatus;
use Modules\HR\Domain\Constants\HrErrorCode;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\DepartmentModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\DesignationModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeAddressModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeContactModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeEmploymentDetailModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeSalaryProfileModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeStatusHistoryModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeUserAccountModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmploymentTypeModel;
use Modules\User\Application\Contracts\UseCases\UserServiceInterface;
use RuntimeException;
use Throwable;

final class HrEmployeeManagementService implements HrEmployeeManagementServiceInterface
{
    public function __construct(
        private readonly EmployeeModel $employees,
        private readonly EmployeeContactModel $contacts,
        private readonly EmployeeAddressModel $addresses,
        private readonly EmployeeEmploymentDetailModel $employmentDetails,
        private readonly EmployeeUserAccountModel $userAccounts,
        private readonly EmployeeStatusHistoryModel $statusHistories,
        private readonly EmployeeSalaryProfileModel $salaryProfiles,
        private readonly DepartmentModel $departments,
        private readonly DesignationModel $designations,
        private readonly EmploymentTypeModel $employmentTypes,
        private readonly UserServiceInterface $userService,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
        private readonly CurrentUserContextAccessorInterface $currentUser,
    ) {
    }

    public function listEmployees(array $filters, int $perPage, int $page): Result
    {
        try {
            $query = $this->employees->newQuery()->where('tenant_id', $this->tenantId());
            $this->applyEmployeeFilters($query, $filters);

            $paginator = $query->orderBy('full_name')
                ->paginate($this->resolvePerPage($perPage), ['*'], 'page', max(1, $page));

            $items = [];
            foreach ($paginator->items() as $item) {
                $items[] = new DataRecord($item->toArray());
            }

            return Result::success(new PagedResult(
                $items,
                $paginator->total(),
                $paginator->currentPage(),
                $paginator->perPage(),
            ));
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function getEmployee(int|string $id): Result
    {
        try {
            $employee = $this->findEmployeeInScope($id);
            if ($employee === null) {
                return $this->notFound('Employee not found.');
            }

            return Result::success($this->employeePayload($employee));
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function createEmployee(array $payload): Result
    {
        try {
            return DB::transaction(function () use ($payload): Result {
                $tenantId = $this->tenantId();
                $orgId = $this->organizationUnitId();

                $employeeCode = $this->normalizeCode((string) ($payload['employee_code'] ?? ''));
                $exists = $this->employees->newQuery()
                    ->where('tenant_id', $tenantId)
                    ->where('employee_code', $employeeCode)
                    ->exists();
                if ($exists) {
                    return $this->failure('Employee code already exists.');
                }

                $status = $this->normalizeStatus($payload['employment_status'] ?? EmployeeStatus::DRAFT);
                $departmentId = $this->toNullableInt($payload['department_id'] ?? null);
                $designationId = $this->toNullableInt($payload['designation_id'] ?? null);
                $employmentTypeId = $this->toNullableInt($payload['employment_type_id'] ?? null);
                $reportingManagerId = $this->toNullableInt($payload['reporting_manager_id'] ?? null);
                $this->validateDepartmentDesignation($departmentId, $designationId);
                $this->validateEmploymentType($employmentTypeId);
                $this->validateReportingManager($reportingManagerId);

                $employee = $this->employees->newQuery()->create([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $orgId,
                    'metadata' => $this->normalizeArray($payload['metadata'] ?? null),
                    'employee_code' => $employeeCode,
                    'first_name' => $this->required((string) ($payload['first_name'] ?? ''), 'First name'),
                    'last_name' => $this->nullableString($payload['last_name'] ?? null),
                    'display_name' => $this->nullableString($payload['display_name'] ?? null),
                    'full_name' => $this->buildFullName($payload),
                    'gender' => $this->nullableString($payload['gender'] ?? null),
                    'date_of_birth' => $payload['date_of_birth'] ?? null,
                    'national_id_number' => $this->nullableString($payload['national_id_number'] ?? null),
                    'passport_number' => $this->nullableString($payload['passport_number'] ?? null),
                    'email' => $this->nullableString($payload['email'] ?? null),
                    'phone' => $this->nullableString($payload['phone'] ?? null),
                    'mobile' => $this->nullableString($payload['mobile'] ?? null),
                    'department_id' => $departmentId,
                    'designation_id' => $designationId,
                    'employment_type_id' => $employmentTypeId,
                    'reporting_manager_id' => $reportingManagerId,
                    'joining_date' => $payload['joining_date'] ?? null,
                    'leaving_date' => $payload['leaving_date'] ?? null,
                    'employment_status' => $status,
                    'is_active' => (bool) ($payload['is_active'] ?? ($status === EmployeeStatus::ACTIVE)),
                    'notes' => $this->nullableString($payload['notes'] ?? null),
                    'created_by' => $this->userId(),
                    'updated_by' => $this->userId(),
                    'activated_by' => $status === EmployeeStatus::ACTIVE ? $this->userId() : null,
                    'activated_at' => $status === EmployeeStatus::ACTIVE ? now() : null,
                    'row_version' => 1,
                ]);

                if (isset($payload['contacts']) && is_array($payload['contacts'])) {
                    $this->replaceContacts((int) $employee->id, $payload['contacts']);
                }
                if (isset($payload['addresses']) && is_array($payload['addresses'])) {
                    $this->replaceAddresses((int) $employee->id, $payload['addresses']);
                }
                if (isset($payload['employment_details']) && is_array($payload['employment_details'])) {
                    $employmentResult = $this->updateEmploymentDetails((int) $employee->id, $payload['employment_details']);
                    if ($employmentResult->isFailure()) {
                        return $employmentResult;
                    }
                }
                if (isset($payload['salary_profile']) && is_array($payload['salary_profile'])) {
                    $this->upsertSalaryProfile((int) $employee->id, $payload['salary_profile']);
                }

                $this->recordStatusHistory((int) $employee->id, null, $status, $payload['status_reason'] ?? null);

                if (($payload['create_user'] ?? false) === true) {
                    $userResult = $this->createEmployeeUserAccess((int) $employee->id, (array) ($payload['user_access'] ?? []));
                    if ($userResult->isFailure()) {
                        return $userResult;
                    }
                }

                if (isset($payload['link_user_id'])) {
                    $linkResult = $this->linkExistingUserToEmployee((int) $employee->id, ['user_id' => $payload['link_user_id']]);
                    if ($linkResult->isFailure()) {
                        return $linkResult;
                    }
                }

                $fresh = $this->findEmployeeInScope((int) $employee->id);
                if ($fresh === null) {
                    return $this->notFound('Employee not found.');
                }

                return Result::success($this->employeePayload($fresh));
            });
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function updateEmployee(int|string $id, array $payload): Result
    {
        try {
            return DB::transaction(function () use ($id, $payload): Result {
                $employee = $this->findEmployeeInScope($id);
                if ($employee === null) {
                    return $this->notFound('Employee not found.');
                }

                $departmentId = array_key_exists('department_id', $payload)
                    ? $this->toNullableInt($payload['department_id'])
                    : $this->toNullableInt($employee->department_id);
                $designationId = array_key_exists('designation_id', $payload)
                    ? $this->toNullableInt($payload['designation_id'])
                    : $this->toNullableInt($employee->designation_id);
                $employmentTypeId = array_key_exists('employment_type_id', $payload)
                    ? $this->toNullableInt($payload['employment_type_id'])
                    : $this->toNullableInt($employee->employment_type_id);
                $reportingManagerId = array_key_exists('reporting_manager_id', $payload)
                    ? $this->toNullableInt($payload['reporting_manager_id'])
                    : $this->toNullableInt($employee->reporting_manager_id);
                $this->validateDepartmentDesignation($departmentId, $designationId);
                $this->validateEmploymentType($employmentTypeId);
                $this->validateReportingManager($reportingManagerId, (int) $employee->id);

                $changes = [
                    'updated_by' => $this->userId(),
                    'row_version' => ((int) $employee->row_version) + 1,
                ];

                foreach (
                    [
                        'first_name',
                        'last_name',
                        'display_name',
                        'gender',
                        'national_id_number',
                        'passport_number',
                        'email',
                        'phone',
                        'mobile',
                        'notes',
                    ] as $textKey
                ) {
                    if (array_key_exists($textKey, $payload)) {
                        $changes[$textKey] = $textKey === 'first_name'
                            ? $this->required((string) $payload[$textKey], 'First name')
                            : $this->nullableString($payload[$textKey]);
                    }
                }

                foreach (['date_of_birth', 'joining_date', 'leaving_date'] as $dateKey) {
                    if (array_key_exists($dateKey, $payload)) {
                        $changes[$dateKey] = $payload[$dateKey];
                    }
                }

                foreach (['department_id', 'designation_id', 'employment_type_id', 'reporting_manager_id'] as $fkKey) {
                    if (array_key_exists($fkKey, $payload)) {
                        $changes[$fkKey] = $this->toNullableInt($payload[$fkKey]);
                    }
                }

                if (array_key_exists('metadata', $payload)) {
                    $changes['metadata'] = $this->normalizeArray($payload['metadata']);
                }

                if (array_key_exists('is_active', $payload)) {
                    $changes['is_active'] = (bool) $payload['is_active'];
                }

                if (array_key_exists('employment_status', $payload)) {
                    $changes['employment_status'] = $this->normalizeStatus($payload['employment_status']);
                }

                $changes['full_name'] = $this->buildFullName(array_merge($employee->toArray(), $changes));

                $employee->fill($changes);
                $employee->save();

                if (array_key_exists('contacts', $payload) && is_array($payload['contacts'])) {
                    $this->replaceContacts((int) $employee->id, $payload['contacts']);
                }
                if (array_key_exists('addresses', $payload) && is_array($payload['addresses'])) {
                    $this->replaceAddresses((int) $employee->id, $payload['addresses']);
                }
                if (array_key_exists('employment_details', $payload) && is_array($payload['employment_details'])) {
                    $employmentResult = $this->updateEmploymentDetails((int) $employee->id, $payload['employment_details']);
                    if ($employmentResult->isFailure()) {
                        return $employmentResult;
                    }
                }
                if (array_key_exists('salary_profile', $payload) && is_array($payload['salary_profile'])) {
                    $this->upsertSalaryProfile((int) $employee->id, $payload['salary_profile']);
                }

                if (($payload['create_user'] ?? false) === true) {
                    $userResult = $this->createEmployeeUserAccess((int) $employee->id, (array) ($payload['user_access'] ?? []));
                    if ($userResult->isFailure()) {
                        return $userResult;
                    }
                }

                if (isset($payload['link_user_id'])) {
                    $linkResult = $this->linkExistingUserToEmployee((int) $employee->id, ['user_id' => $payload['link_user_id']]);
                    if ($linkResult->isFailure()) {
                        return $linkResult;
                    }
                }

                $fresh = $this->findEmployeeInScope((int) $employee->id);
                if ($fresh === null) {
                    return $this->notFound('Employee not found.');
                }

                return Result::success($this->employeePayload($fresh));
            });
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function changeEmployeeStatus(int|string $id, string $toStatus, ?string $reason = null): Result
    {
        try {
            return DB::transaction(function () use ($id, $toStatus, $reason): Result {
                $employee = $this->findEmployeeInScope($id);
                if ($employee === null) {
                    return $this->notFound('Employee not found.');
                }

                $fromStatus = (string) $employee->employment_status;
                $target = $this->normalizeStatus($toStatus);

                if ($fromStatus === $target) {
                    return Result::success($this->employeePayload($employee));
                }

                $employee->employment_status = $target;
                $employee->is_active = $target === EmployeeStatus::ACTIVE;
                $employee->updated_by = $this->userId();
                $employee->row_version = ((int) $employee->row_version) + 1;

                if ($target === EmployeeStatus::ACTIVE) {
                    $employee->activated_at = now();
                    $employee->activated_by = $this->userId();
                    $employee->deactivated_at = null;
                    $employee->suspended_at = null;
                } elseif ($target === EmployeeStatus::INACTIVE) {
                    $employee->deactivated_at = now();
                    $employee->deactivated_by = $this->userId();
                } elseif ($target === EmployeeStatus::SUSPENDED) {
                    $employee->suspended_at = now();
                    $employee->suspended_by = $this->userId();
                } elseif (in_array($target, [EmployeeStatus::TERMINATED, EmployeeStatus::RESIGNED], true)) {
                    $employee->terminated_at = now();
                    $employee->terminated_by = $this->userId();
                    $employee->leaving_date = $employee->leaving_date ?? now()->toDateString();
                } elseif ($target === EmployeeStatus::ARCHIVED) {
                    $employee->archived_at = now();
                    $employee->archived_by = $this->userId();
                }

                $employee->save();
                $this->recordStatusHistory((int) $employee->id, $fromStatus, $target, $reason);

                return Result::success($this->employeePayload($employee));
            });
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function safeDeleteEmployee(int|string $id): Result
    {
        try {
            $employee = $this->findEmployeeInScope($id);
            if ($employee === null) {
                return $this->notFound('Employee not found.');
            }

            if ((string) $employee->employment_status !== EmployeeStatus::DRAFT) {
                return $this->failure('Only draft employees can be deleted safely.');
            }

            $employee->delete();

            return Result::success(true);
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function listDepartments(array $filters, int $perPage, int $page): Result
    {
        try {
            $query = $this->departments->newQuery()->where('tenant_id', $this->tenantId());
            if (isset($filters['is_active'])) {
                $query->where('is_active', (bool) $filters['is_active']);
            }
            if (isset($filters['search']) && is_string($filters['search']) && trim($filters['search']) !== '') {
                $term = trim($filters['search']);
                $query->where(function ($inner) use ($term): void {
                    $inner->where('department_code', 'like', '%' . $term . '%')
                        ->orWhere('department_name', 'like', '%' . $term . '%');
                });
            }

            $paginator = $query->orderBy('department_name')
                ->paginate($this->resolvePerPage($perPage), ['*'], 'page', max(1, $page));

            $items = [];
            foreach ($paginator->items() as $item) {
                $items[] = new DataRecord($item->toArray());
            }

            return Result::success(new PagedResult($items, $paginator->total(), $paginator->currentPage(), $paginator->perPage()));
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function getDepartment(int|string $id): Result
    {
        try {
            $record = $this->departments->newQuery()
                ->where('tenant_id', $this->tenantId())
                ->where('id', (int) $id)
                ->first();

            if ($record === null) {
                return $this->notFound('Department not found.');
            }

            return Result::success(new DataRecord($record->toArray()));
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function createDepartment(array $payload): Result
    {
        try {
            $tenantId = $this->tenantId();
            $code = $this->normalizeCode((string) ($payload['department_code'] ?? ''));
            $exists = $this->departments->newQuery()
                ->where('tenant_id', $tenantId)
                ->where('department_code', $code)
                ->exists();
            if ($exists) {
                return $this->failure('Department code already exists.');
            }

            $department = $this->departments->newQuery()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $this->organizationUnitId(),
                'metadata' => $this->normalizeArray($payload['metadata'] ?? null),
                'parent_id' => $this->toNullableInt($payload['parent_id'] ?? null),
                'manager_employee_id' => $this->toNullableInt($payload['manager_employee_id'] ?? null),
                'department_code' => $code,
                'department_name' => $this->required((string) ($payload['department_name'] ?? ''), 'Department name'),
                'depth' => (int) ($payload['depth'] ?? 0),
                'path' => $this->nullableString($payload['path'] ?? null),
                'is_active' => (bool) ($payload['is_active'] ?? true),
                'description' => $this->nullableString($payload['description'] ?? null),
                'created_by' => $this->userId(),
                'updated_by' => $this->userId(),
                'row_version' => 1,
            ]);

            return Result::success(new DataRecord($department->toArray()));
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function updateDepartment(int|string $id, array $payload): Result
    {
        try {
            $record = $this->departments->newQuery()
                ->where('tenant_id', $this->tenantId())
                ->where('id', (int) $id)
                ->first();
            if ($record === null) {
                return $this->notFound('Department not found.');
            }

            $changes = [
                'updated_by' => $this->userId(),
                'row_version' => ((int) $record->row_version) + 1,
            ];
            foreach (['department_name', 'path', 'description'] as $textKey) {
                if (array_key_exists($textKey, $payload)) {
                    $changes[$textKey] = $this->nullableString($payload[$textKey]);
                }
            }
            foreach (['parent_id', 'manager_employee_id'] as $fkKey) {
                if (array_key_exists($fkKey, $payload)) {
                    $changes[$fkKey] = $this->toNullableInt($payload[$fkKey]);
                }
            }
            if (array_key_exists('department_code', $payload)) {
                $changes['department_code'] = $this->normalizeCode((string) $payload['department_code']);
            }
            if (array_key_exists('is_active', $payload)) {
                $changes['is_active'] = (bool) $payload['is_active'];
            }
            if (array_key_exists('depth', $payload)) {
                $changes['depth'] = max(0, (int) $payload['depth']);
            }
            if (array_key_exists('metadata', $payload)) {
                $changes['metadata'] = $this->normalizeArray($payload['metadata']);
            }

            $record->fill($changes);
            $record->save();

            return Result::success(new DataRecord($record->toArray()));
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function listDesignations(array $filters, int $perPage, int $page): Result
    {
        try {
            $query = $this->designations->newQuery()->where('tenant_id', $this->tenantId());
            if (isset($filters['department_id']) && $filters['department_id'] !== null) {
                $query->where('department_id', (int) $filters['department_id']);
            }
            if (isset($filters['is_active'])) {
                $query->where('is_active', (bool) $filters['is_active']);
            }
            if (isset($filters['search']) && is_string($filters['search']) && trim($filters['search']) !== '') {
                $term = trim($filters['search']);
                $query->where(function ($inner) use ($term): void {
                    $inner->where('designation_code', 'like', '%' . $term . '%')
                        ->orWhere('designation_name', 'like', '%' . $term . '%');
                });
            }

            $paginator = $query->orderBy('designation_name')
                ->paginate($this->resolvePerPage($perPage), ['*'], 'page', max(1, $page));

            $items = [];
            foreach ($paginator->items() as $item) {
                $items[] = new DataRecord($item->toArray());
            }

            return Result::success(new PagedResult($items, $paginator->total(), $paginator->currentPage(), $paginator->perPage()));
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function getDesignation(int|string $id): Result
    {
        try {
            $record = $this->designations->newQuery()
                ->where('tenant_id', $this->tenantId())
                ->where('id', (int) $id)
                ->first();

            if ($record === null) {
                return $this->notFound('Designation not found.');
            }

            return Result::success(new DataRecord($record->toArray()));
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function createDesignation(array $payload): Result
    {
        try {
            $tenantId = $this->tenantId();
            $code = $this->normalizeCode((string) ($payload['designation_code'] ?? ''));
            $exists = $this->designations->newQuery()
                ->where('tenant_id', $tenantId)
                ->where('designation_code', $code)
                ->exists();
            if ($exists) {
                return $this->failure('Designation code already exists.');
            }

            $designation = $this->designations->newQuery()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $this->organizationUnitId(),
                'metadata' => $this->normalizeArray($payload['metadata'] ?? null),
                'department_id' => $this->toNullableInt($payload['department_id'] ?? null),
                'designation_code' => $code,
                'designation_name' => $this->required((string) ($payload['designation_name'] ?? ''), 'Designation name'),
                'is_active' => (bool) ($payload['is_active'] ?? true),
                'description' => $this->nullableString($payload['description'] ?? null),
                'created_by' => $this->userId(),
                'updated_by' => $this->userId(),
                'row_version' => 1,
            ]);

            return Result::success(new DataRecord($designation->toArray()));
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function updateDesignation(int|string $id, array $payload): Result
    {
        try {
            $record = $this->designations->newQuery()
                ->where('tenant_id', $this->tenantId())
                ->where('id', (int) $id)
                ->first();
            if ($record === null) {
                return $this->notFound('Designation not found.');
            }

            $changes = [
                'updated_by' => $this->userId(),
                'row_version' => ((int) $record->row_version) + 1,
            ];
            if (array_key_exists('designation_code', $payload)) {
                $changes['designation_code'] = $this->normalizeCode((string) $payload['designation_code']);
            }
            if (array_key_exists('designation_name', $payload)) {
                $changes['designation_name'] = $this->required((string) $payload['designation_name'], 'Designation name');
            }
            if (array_key_exists('department_id', $payload)) {
                $changes['department_id'] = $this->toNullableInt($payload['department_id']);
            }
            if (array_key_exists('description', $payload)) {
                $changes['description'] = $this->nullableString($payload['description']);
            }
            if (array_key_exists('is_active', $payload)) {
                $changes['is_active'] = (bool) $payload['is_active'];
            }
            if (array_key_exists('metadata', $payload)) {
                $changes['metadata'] = $this->normalizeArray($payload['metadata']);
            }

            $record->fill($changes);
            $record->save();

            return Result::success(new DataRecord($record->toArray()));
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function listEmployeeContacts(int|string $employeeId): Result
    {
        try {
            $employee = $this->findEmployeeInScope($employeeId);
            if ($employee === null) {
                return $this->notFound('Employee not found.');
            }

            $records = $this->contacts->newQuery()
                ->where('tenant_id', $this->tenantId())
                ->where('employee_id', (int) $employee->id)
                ->orderByDesc('is_primary')
                ->orderByDesc('is_emergency')
                ->orderBy('id')
                ->get();

            return Result::success($records->map(fn ($item): array => $item->toArray())->all());
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function createEmployeeContact(int|string $employeeId, array $payload): Result
    {
        try {
            $employee = $this->findEmployeeInScope($employeeId);
            if ($employee === null) {
                return $this->notFound('Employee not found.');
            }

            $record = $this->contacts->newQuery()->create($this->contactAttributes((int) $employee->id, $payload));

            return Result::success(new DataRecord($record->toArray()));
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function updateEmployeeContact(int|string $employeeId, int|string $contactId, array $payload): Result
    {
        try {
            $employee = $this->findEmployeeInScope($employeeId);
            if ($employee === null) {
                return $this->notFound('Employee not found.');
            }

            $record = $this->contacts->newQuery()
                ->where('tenant_id', $this->tenantId())
                ->where('employee_id', (int) $employee->id)
                ->where('id', (int) $contactId)
                ->first();
            if ($record === null) {
                return $this->notFound('Employee contact not found.');
            }

            $changes = [
                'updated_by' => $this->userId(),
                'row_version' => ((int) $record->row_version) + 1,
            ];
            foreach (['contact_type', 'contact_name', 'relationship', 'email', 'phone', 'mobile', 'notes'] as $key) {
                if (array_key_exists($key, $payload)) {
                    $changes[$key] = $key === 'contact_name'
                        ? $this->required((string) $payload[$key], 'Contact name')
                        : $this->nullableString($payload[$key]);
                }
            }
            foreach (['is_primary', 'is_emergency', 'is_active'] as $boolKey) {
                if (array_key_exists($boolKey, $payload)) {
                    $changes[$boolKey] = (bool) $payload[$boolKey];
                }
            }
            if (array_key_exists('metadata', $payload)) {
                $changes['metadata'] = $this->normalizeArray($payload['metadata']);
            }

            $record->fill($changes);
            $record->save();

            return Result::success(new DataRecord($record->toArray()));
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function deactivateEmployeeContact(int|string $employeeId, int|string $contactId): Result
    {
        return $this->updateEmployeeContact($employeeId, $contactId, ['is_active' => false, 'is_primary' => false]);
    }

    public function listEmployeeAddresses(int|string $employeeId): Result
    {
        try {
            $employee = $this->findEmployeeInScope($employeeId);
            if ($employee === null) {
                return $this->notFound('Employee not found.');
            }

            $records = $this->addresses->newQuery()
                ->where('tenant_id', $this->tenantId())
                ->where('employee_id', (int) $employee->id)
                ->orderByDesc('is_primary')
                ->orderBy('id')
                ->get();

            return Result::success($records->map(fn ($item): array => $item->toArray())->all());
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function createEmployeeAddress(int|string $employeeId, array $payload): Result
    {
        try {
            $employee = $this->findEmployeeInScope($employeeId);
            if ($employee === null) {
                return $this->notFound('Employee not found.');
            }

            $record = $this->addresses->newQuery()->create($this->addressAttributes((int) $employee->id, $payload));

            return Result::success(new DataRecord($record->toArray()));
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function updateEmployeeAddress(int|string $employeeId, int|string $addressId, array $payload): Result
    {
        try {
            $employee = $this->findEmployeeInScope($employeeId);
            if ($employee === null) {
                return $this->notFound('Employee not found.');
            }

            $record = $this->addresses->newQuery()
                ->where('tenant_id', $this->tenantId())
                ->where('employee_id', (int) $employee->id)
                ->where('id', (int) $addressId)
                ->first();
            if ($record === null) {
                return $this->notFound('Employee address not found.');
            }

            $changes = [
                'updated_by' => $this->userId(),
                'row_version' => ((int) $record->row_version) + 1,
            ];
            foreach (
                ['address_type', 'address_line_1', 'address_line_2', 'city', 'state_province', 'postal_code', 'country_name'] as $key
            ) {
                if (array_key_exists($key, $payload)) {
                    $changes[$key] = in_array($key, ['address_line_1', 'city'], true)
                        ? $this->required((string) $payload[$key], Str::replace('_', ' ', $key))
                        : $this->nullableString($payload[$key]);
                }
            }
            if (array_key_exists('country_id', $payload)) {
                $changes['country_id'] = $this->toNullableInt($payload['country_id']);
            }
            foreach (['is_primary', 'is_active'] as $boolKey) {
                if (array_key_exists($boolKey, $payload)) {
                    $changes[$boolKey] = (bool) $payload[$boolKey];
                }
            }
            if (array_key_exists('metadata', $payload)) {
                $changes['metadata'] = $this->normalizeArray($payload['metadata']);
            }

            $record->fill($changes);
            $record->save();

            return Result::success(new DataRecord($record->toArray()));
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function deactivateEmployeeAddress(int|string $employeeId, int|string $addressId): Result
    {
        return $this->updateEmployeeAddress($employeeId, $addressId, ['is_active' => false, 'is_primary' => false]);
    }

    public function getEmploymentDetails(int|string $employeeId): Result
    {
        try {
            $employee = $this->findEmployeeInScope($employeeId);
            if ($employee === null) {
                return $this->notFound('Employee not found.');
            }

            $record = $this->employmentDetails->newQuery()
                ->where('tenant_id', $this->tenantId())
                ->where('employee_id', (int) $employee->id)
                ->first();

            return Result::success($record?->toArray());
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function updateEmploymentDetails(int|string $employeeId, array $payload): Result
    {
        try {
            $employee = $this->findEmployeeInScope($employeeId);
            if ($employee === null) {
                return $this->notFound('Employee not found.');
            }

            $managerId = $this->toNullableInt($payload['reporting_manager_id'] ?? null);
            $this->validateReportingManager($managerId, (int) $employee->id);

            $attributes = [
                'department_id' => $this->toNullableInt($payload['department_id'] ?? $employee->department_id),
                'designation_id' => $this->toNullableInt($payload['designation_id'] ?? $employee->designation_id),
                'employment_type_id' => $this->toNullableInt($payload['employment_type_id'] ?? $employee->employment_type_id),
                'employment_status' => $this->normalizeStatus($payload['employment_status'] ?? $employee->employment_status),
                'joining_date' => $payload['joining_date'] ?? $employee->joining_date,
                'probation_end_date' => $payload['probation_end_date'] ?? null,
                'confirmation_date' => $payload['confirmation_date'] ?? $employee->confirmation_date,
                'leaving_date' => $payload['leaving_date'] ?? $employee->leaving_date,
                'reporting_manager_id' => $managerId,
                'work_location_id' => $this->toNullableInt($payload['work_location_id'] ?? null),
                'shift_id' => $this->toNullableInt($payload['shift_id'] ?? null),
                'is_active' => (bool) ($payload['is_active'] ?? true),
                'metadata' => $this->normalizeArray($payload['metadata'] ?? null),
                'updated_by' => $this->userId(),
            ];

            $this->validateDepartmentDesignation($attributes['department_id'], $attributes['designation_id']);
            $this->validateEmploymentType($attributes['employment_type_id']);

            $record = $this->employmentDetails->newQuery()
                ->where('tenant_id', $this->tenantId())
                ->where('employee_id', (int) $employee->id)
                ->first();

            if ($record === null) {
                $created = $this->employmentDetails->newQuery()->create(array_merge($attributes, [
                    'tenant_id' => $this->tenantId(),
                    'organization_unit_id' => $this->organizationUnitId(),
                    'employee_id' => (int) $employee->id,
                    'created_by' => $this->userId(),
                    'row_version' => 1,
                ]));

                return Result::success(new DataRecord($created->toArray()));
            }

            $record->fill(array_merge($attributes, ['row_version' => ((int) $record->row_version) + 1]));
            $record->save();

            return Result::success(new DataRecord($record->toArray()));
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function listEmployeeUserAccounts(int|string $employeeId): Result
    {
        try {
            $employee = $this->findEmployeeInScope($employeeId);
            if ($employee === null) {
                return $this->notFound('Employee not found.');
            }

            $records = $this->userAccounts->newQuery()
                ->where('tenant_id', $this->tenantId())
                ->where('employee_id', (int) $employee->id)
                ->orderByDesc('is_primary')
                ->orderBy('id')
                ->get();

            return Result::success($records->map(fn ($item): array => $item->toArray())->all());
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function createEmployeeUserAccess(int|string $employeeId, array $payload): Result
    {
        try {
            return DB::transaction(function () use ($employeeId, $payload): Result {
                $employee = $this->findEmployeeInScope($employeeId);
                if ($employee === null) {
                    return $this->notFound('Employee not found.');
                }

                $userPayload = (array) ($payload['user'] ?? []);
                $email = $this->required((string) ($userPayload['email'] ?? ''), 'User email');
                $fullName = $this->nullableString($userPayload['name'] ?? null) ?? (string) $employee->full_name;
                $parts = preg_split('/\s+/', trim($fullName)) ?: [];
                $firstName = $parts[0] ?? (string) $employee->first_name;
                $lastName = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : null;

                $createResult = $this->userService->create([
                    'tenant_id' => $this->tenantId(),
                    'organization_unit_id' => $this->organizationUnitId(),
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'password' => (string) ($userPayload['password'] ?? Str::random(32)),
                    'status' => 'active',
                    'metadata' => [
                        'created_from' => 'hr_module',
                        'employee_id' => (int) $employee->id,
                    ],
                ]);

                if ($createResult->isFailure()) {
                    return Result::failure($createResult->errorOrFail());
                }

                $value = $createResult->valueOrFail();
                $userId = $value instanceof DataRecord ? (int) $value->id() : (int) ($value['id'] ?? 0);
                if ($userId <= 0) {
                    return $this->failure('User creation did not return a valid identifier.');
                }

                return $this->linkExistingUserToEmployee((int) $employee->id, [
                    'user_id' => $userId,
                    'access_role' => $payload['access_role'] ?? 'employee_portal',
                    'is_primary' => $payload['is_primary'] ?? false,
                    'invited' => $payload['invited'] ?? true,
                    'metadata' => $payload['metadata'] ?? null,
                ]);
            });
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function linkExistingUserToEmployee(int|string $employeeId, array $payload): Result
    {
        try {
            $employee = $this->findEmployeeInScope($employeeId);
            if ($employee === null) {
                return $this->notFound('Employee not found.');
            }

            $userId = $this->toNullableInt($payload['user_id'] ?? null);
            if ($userId === null) {
                return $this->failure('User id is required.');
            }

            $exists = $this->userAccounts->newQuery()
                ->where('tenant_id', $this->tenantId())
                ->where('employee_id', (int) $employee->id)
                ->where('user_id', $userId)
                ->exists();
            if ($exists) {
                return $this->failure('User is already linked to employee.');
            }

            $isPrimary = (bool) ($payload['is_primary'] ?? false);
            if ($isPrimary) {
                $this->userAccounts->newQuery()
                    ->where('tenant_id', $this->tenantId())
                    ->where('employee_id', (int) $employee->id)
                    ->update(['is_primary' => false, 'updated_at' => now()]);
            }

            $invited = (bool) ($payload['invited'] ?? false);
            $record = $this->userAccounts->newQuery()->create([
                'tenant_id' => $this->tenantId(),
                'organization_unit_id' => $this->organizationUnitId(),
                'metadata' => $this->normalizeArray($payload['metadata'] ?? null),
                'employee_id' => (int) $employee->id,
                'user_id' => $userId,
                'access_role' => $this->nullableString($payload['access_role'] ?? null) ?? 'employee_portal',
                'is_primary' => $isPrimary,
                'access_status' => $invited ? 'invited' : 'active',
                'invited_at' => $invited ? now() : null,
                'activated_at' => $invited ? null : now(),
                'linked_user_by' => $this->userId(),
                'invited_by' => $invited ? $this->userId() : null,
                'created_by' => $this->userId(),
                'updated_by' => $this->userId(),
                'row_version' => 1,
            ]);

            return Result::success(new DataRecord($record->toArray()));
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function deactivateEmployeeUserAccess(int|string $employeeId, int|string $accessId, array $payload): Result
    {
        try {
            $recordResult = $this->resolveUserAccess($employeeId, $accessId);
            if ($recordResult->isFailure()) {
                return $recordResult;
            }

            $record = $recordResult->valueOrFail();
            $metadata = $this->normalizeArray($record->metadata ?? null) ?? [];
            if (isset($payload['reason']) && is_string($payload['reason']) && trim($payload['reason']) !== '') {
                $metadata['deactivation_reason'] = trim($payload['reason']);
            }

            $record->fill([
                'access_status' => 'inactive',
                'is_primary' => false,
                'deactivated_at' => now(),
                'metadata' => $metadata,
                'updated_by' => $this->userId(),
                'row_version' => ((int) $record->row_version) + 1,
            ]);
            $record->save();

            return Result::success(new DataRecord($record->toArray()));
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function unlinkEmployeeUserAccess(int|string $employeeId, int|string $accessId): Result
    {
        try {
            $recordResult = $this->resolveUserAccess($employeeId, $accessId);
            if ($recordResult->isFailure()) {
                return $recordResult;
            }

            $record = $recordResult->valueOrFail();
            $record->delete();

            return Result::success(true);
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function lookupEmployees(string $search, int $limit = 20): Result
    {
        try {
            $query = $this->employees->newQuery()
                ->where('tenant_id', $this->tenantId())
                ->orderBy('full_name');

            $term = trim($search);
            if ($term !== '') {
                $query->where(function ($inner) use ($term): void {
                    $inner->where('employee_code', 'like', '%' . $term . '%')
                        ->orWhere('full_name', 'like', '%' . $term . '%')
                        ->orWhere('email', 'like', '%' . $term . '%');
                });
            }

            $records = $query->limit(max(1, min($limit, 100)))
                ->get(['id', 'employee_code', 'full_name', 'department_id', 'designation_id', 'employment_status', 'is_active']);

            return Result::success($records->map(fn ($item): array => $item->toArray())->all());
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function listActiveEmployees(int $limit = 50): Result
    {
        try {
            $records = $this->employees->newQuery()
                ->where('tenant_id', $this->tenantId())
                ->where('employment_status', EmployeeStatus::ACTIVE)
                ->where('is_active', true)
                ->orderBy('full_name')
                ->limit(max(1, min($limit, 200)))
                ->get();

            return Result::success($records->map(fn ($item): array => $item->toArray())->all());
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function validateEmployeeForAssignmentContext(int|string $employeeId, string $assignmentContext): Result
    {
        try {
            return $this->validateEmployeeForAssignment($employeeId, $assignmentContext);
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function getEmployeesByDepartment(int|string $departmentId): Result
    {
        try {
            $records = $this->employees->newQuery()
                ->where('tenant_id', $this->tenantId())
                ->where('department_id', (int) $departmentId)
                ->orderBy('full_name')
                ->get();

            return Result::success($records->map(fn ($item): array => $item->toArray())->all());
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function getEmployeesByDesignation(int|string $designationId): Result
    {
        try {
            $records = $this->employees->newQuery()
                ->where('tenant_id', $this->tenantId())
                ->where('designation_id', (int) $designationId)
                ->orderBy('full_name')
                ->get();

            return Result::success($records->map(fn ($item): array => $item->toArray())->all());
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    private function resolveUserAccess(int|string $employeeId, int|string $accessId): Result
    {
        $employee = $this->findEmployeeInScope($employeeId);
        if ($employee === null) {
            return $this->notFound('Employee not found.');
        }

        $record = $this->userAccounts->newQuery()
            ->where('tenant_id', $this->tenantId())
            ->where('employee_id', (int) $employee->id)
            ->where('id', (int) $accessId)
            ->first();

        if ($record === null) {
            return $this->notFound('Employee user access not found.');
        }

        return Result::success($record);
    }

    /**
     * @param array<int, array<string, mixed>> $contacts
     */
    private function replaceContacts(int $employeeId, array $contacts): void
    {
        $this->contacts->newQuery()
            ->where('tenant_id', $this->tenantId())
            ->where('employee_id', $employeeId)
            ->delete();

        foreach ($contacts as $entry) {
            if (! is_array($entry)) {
                continue;
            }
            $this->contacts->newQuery()->create($this->contactAttributes($employeeId, $entry));
        }
    }

    /**
     * @param array<int, array<string, mixed>> $addresses
     */
    private function replaceAddresses(int $employeeId, array $addresses): void
    {
        $this->addresses->newQuery()
            ->where('tenant_id', $this->tenantId())
            ->where('employee_id', $employeeId)
            ->delete();

        foreach ($addresses as $entry) {
            if (! is_array($entry)) {
                continue;
            }
            $this->addresses->newQuery()->create($this->addressAttributes($employeeId, $entry));
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function contactAttributes(int $employeeId, array $payload): array
    {
        return [
            'tenant_id' => $this->tenantId(),
            'organization_unit_id' => $this->organizationUnitId(),
            'metadata' => $this->normalizeArray($payload['metadata'] ?? null),
            'employee_id' => $employeeId,
            'contact_type' => $this->nullableString($payload['contact_type'] ?? null) ?? 'family',
            'contact_name' => $this->required((string) ($payload['contact_name'] ?? ''), 'Contact name'),
            'relationship' => $this->nullableString($payload['relationship'] ?? null),
            'email' => $this->nullableString($payload['email'] ?? null),
            'phone' => $this->nullableString($payload['phone'] ?? null),
            'mobile' => $this->nullableString($payload['mobile'] ?? null),
            'is_primary' => (bool) ($payload['is_primary'] ?? false),
            'is_emergency' => (bool) ($payload['is_emergency'] ?? false),
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'notes' => $this->nullableString($payload['notes'] ?? null),
            'created_by' => $this->userId(),
            'updated_by' => $this->userId(),
            'row_version' => 1,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function addressAttributes(int $employeeId, array $payload): array
    {
        return [
            'tenant_id' => $this->tenantId(),
            'organization_unit_id' => $this->organizationUnitId(),
            'metadata' => $this->normalizeArray($payload['metadata'] ?? null),
            'employee_id' => $employeeId,
            'address_type' => $this->nullableString($payload['address_type'] ?? null) ?? 'current',
            'address_line_1' => $this->required((string) ($payload['address_line_1'] ?? ''), 'Address line 1'),
            'address_line_2' => $this->nullableString($payload['address_line_2'] ?? null),
            'city' => $this->required((string) ($payload['city'] ?? ''), 'City'),
            'state_province' => $this->nullableString($payload['state_province'] ?? null),
            'postal_code' => $this->nullableString($payload['postal_code'] ?? null),
            'country_id' => $this->toNullableInt($payload['country_id'] ?? null),
            'country_name' => $this->nullableString($payload['country_name'] ?? null),
            'is_primary' => (bool) ($payload['is_primary'] ?? false),
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'created_by' => $this->userId(),
            'updated_by' => $this->userId(),
            'row_version' => 1,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function upsertSalaryProfile(int $employeeId, array $payload): void
    {
        $record = $this->salaryProfiles->newQuery()
            ->where('tenant_id', $this->tenantId())
            ->where('employee_id', $employeeId)
            ->where('is_active', true)
            ->first();

        $attributes = [
            'organization_unit_id' => $this->organizationUnitId(),
            'metadata' => $this->normalizeArray($payload['metadata'] ?? null),
            'salary_type' => $this->nullableString($payload['salary_type'] ?? null) ?? 'monthly',
            'basic_salary' => $payload['basic_salary'] ?? null,
            'hourly_rate' => $payload['hourly_rate'] ?? null,
            'overtime_rate' => $payload['overtime_rate'] ?? null,
            'payment_method_id' => $this->toNullableInt($payload['payment_method_id'] ?? null),
            'bank_account_reference' => $this->nullableString($payload['bank_account_reference'] ?? null),
            'effective_from' => $payload['effective_from'] ?? null,
            'effective_to' => $payload['effective_to'] ?? null,
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'updated_by' => $this->userId(),
        ];

        if ($record === null) {
            $this->salaryProfiles->newQuery()->create(array_merge($attributes, [
                'tenant_id' => $this->tenantId(),
                'employee_id' => $employeeId,
                'created_by' => $this->userId(),
                'row_version' => 1,
            ]));

            return;
        }

        $record->fill(array_merge($attributes, ['row_version' => ((int) $record->row_version) + 1]));
        $record->save();
    }

    private function recordStatusHistory(int $employeeId, ?string $fromStatus, string $toStatus, mixed $reason): void
    {
        $this->statusHistories->newQuery()->create([
            'tenant_id' => $this->tenantId(),
            'organization_unit_id' => $this->organizationUnitId(),
            'metadata' => null,
            'employee_id' => $employeeId,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'reason' => $this->nullableString($reason),
            'changed_by' => $this->userId(),
            'changed_at' => now(),
            'row_version' => 1,
        ]);
    }

    private function applyEmployeeFilters(mixed $query, array $filters): void
    {
        if (isset($filters['department_id']) && $filters['department_id'] !== null) {
            $query->where('department_id', (int) $filters['department_id']);
        }
        if (isset($filters['designation_id']) && $filters['designation_id'] !== null) {
            $query->where('designation_id', (int) $filters['designation_id']);
        }
        if (isset($filters['employment_status']) && is_string($filters['employment_status']) && trim($filters['employment_status']) !== '') {
            $query->where('employment_status', trim($filters['employment_status']));
        }
        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null) {
            $query->where('is_active', (bool) $filters['is_active']);
        }
        if (isset($filters['search']) && is_string($filters['search']) && trim($filters['search']) !== '') {
            $term = trim($filters['search']);
            $query->where(function ($inner) use ($term): void {
                $inner->where('employee_code', 'like', '%' . $term . '%')
                    ->orWhere('full_name', 'like', '%' . $term . '%')
                    ->orWhere('email', 'like', '%' . $term . '%')
                    ->orWhere('mobile', 'like', '%' . $term . '%');
            });
        }
    }

    private function validateDepartmentDesignation(?int $departmentId, ?int $designationId): void
    {
        if ($departmentId !== null) {
            $departmentExists = $this->departments->newQuery()
                ->where('tenant_id', $this->tenantId())
                ->where('id', $departmentId)
                ->exists();
            if (! $departmentExists) {
                throw new RuntimeException('Invalid department selected.');
            }
        }

        if ($designationId !== null) {
            $designation = $this->designations->newQuery()
                ->where('tenant_id', $this->tenantId())
                ->where('id', $designationId)
                ->first();
            if ($designation === null) {
                throw new RuntimeException('Invalid designation selected.');
            }

            if ($departmentId !== null && $designation->department_id !== null && (int) $designation->department_id !== $departmentId) {
                throw new RuntimeException('Designation does not belong to selected department.');
            }
        }
    }

    private function validateEmploymentType(?int $employmentTypeId): void
    {
        if ($employmentTypeId === null) {
            return;
        }

        $exists = $this->employmentTypes->newQuery()
            ->where('tenant_id', $this->tenantId())
            ->where('id', $employmentTypeId)
            ->exists();

        if (! $exists) {
            throw new RuntimeException('Invalid employment type selected.');
        }
    }

    private function validateReportingManager(?int $reportingManagerId, ?int $employeeId = null): void
    {
        if ($reportingManagerId === null) {
            return;
        }

        if ($employeeId !== null && $reportingManagerId === $employeeId) {
            throw new RuntimeException('Reporting manager cannot be the same employee.');
        }

        $exists = $this->employees->newQuery()
            ->where('tenant_id', $this->tenantId())
            ->where('id', $reportingManagerId)
            ->exists();

        if (! $exists) {
            throw new RuntimeException('Invalid reporting manager selected.');
        }
    }

    private function validateEmployeeForAssignment(int|string $employeeId, string $assignmentType): Result
    {
        $employee = $this->findEmployeeInScope($employeeId);
        if ($employee === null) {
            return $this->notFound('Employee not found.');
        }

        $errors = [];
        if ((string) $employee->employment_status !== EmployeeStatus::ACTIVE || ! (bool) $employee->is_active) {
            $errors[] = 'Employee is not active for assignment.';
        }

        return Result::success([
            'assignment_context' => $this->normalizeContext($assignmentType),
            'assignment_type' => $this->normalizeContext($assignmentType),
            'employee_id' => (int) $employee->id,
            'employee_code' => $employee->employee_code,
            'employee_name' => $employee->full_name,
            'is_assignable' => $errors === [],
            'errors' => $errors,
        ]);
    }

    private function normalizeContext(string $value): string
    {
        $normalized = \Illuminate\Support\Str::of($value)->trim()->lower()->replace([' ', '-'], '_')->toString();

        return $normalized === '' ? 'generic' : $normalized;
    }

    private function findEmployeeInScope(int|string $id): ?EmployeeModel
    {
        return $this->employees->newQuery()
            ->where('tenant_id', $this->tenantId())
            ->where('id', (int) $id)
            ->first();
    }

    private function employeePayload(EmployeeModel $employee): DataRecord
    {
        $employeeId = (int) $employee->id;
        $tenantId = $this->tenantId();

        $payload = $employee->toArray();
        $payload['contacts'] = $this->contacts->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->orderByDesc('is_primary')
            ->orderByDesc('is_emergency')
            ->orderBy('id')
            ->get()
            ->map(fn ($item): array => $item->toArray())
            ->all();
        $payload['addresses'] = $this->addresses->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get()
            ->map(fn ($item): array => $item->toArray())
            ->all();
        $payload['employment_details'] = $this->employmentDetails->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->first()?->toArray();
        $payload['salary_profile'] = $this->salaryProfiles->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->where('is_active', true)
            ->first()?->toArray();
        $payload['user_accesses'] = $this->userAccounts->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get()
            ->map(fn ($item): array => $item->toArray())
            ->all();

        return new DataRecord($payload);
    }

    private function resolvePerPage(int $perPage): int
    {
        if ($perPage <= 0) {
            return (int) config('hr.pagination.default_per_page', 20);
        }

        return min($perPage, (int) config('hr.pagination.max_per_page', 200));
    }

    private function tenantId(): int
    {
        $tenantId = $this->currentTenant->currentTenantId();
        if ($tenantId === null || $tenantId <= 0) {
            throw new RuntimeException('Current tenant context is required.');
        }

        return $tenantId;
    }

    private function organizationUnitId(): ?int
    {
        return $this->currentOrganizationUnit->currentOrganizationUnitId();
    }

    private function userId(): ?int
    {
        return $this->currentUser->currentUserId();
    }

    private function normalizeStatus(mixed $status): string
    {
        $resolved = is_string($status) ? strtolower(trim($status)) : '';
        if ($resolved === '') {
            $resolved = EmployeeStatus::DRAFT;
        }
        if (! in_array($resolved, EmployeeStatus::values(), true)) {
            throw new RuntimeException('Invalid employee status value.');
        }

        return $resolved;
    }

    private function normalizeCode(string $value): string
    {
        $normalized = strtoupper(trim($value));
        if ($normalized === '') {
            throw new RuntimeException('Code is required.');
        }

        return $normalized;
    }

    private function required(string $value, string $label): string
    {
        $normalized = trim($value);
        if ($normalized === '') {
            throw new RuntimeException($label . ' is required.');
        }

        return $normalized;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function normalizeArray(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        return is_array($value) ? $value : null;
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $intValue = (int) $value;

        return $intValue > 0 ? $intValue : null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function buildFullName(array $payload): string
    {
        $firstName = $this->nullableString($payload['first_name'] ?? null) ?? '';
        $lastName = $this->nullableString($payload['last_name'] ?? null);
        $displayName = $this->nullableString($payload['display_name'] ?? null);
        if ($displayName !== null) {
            return $displayName;
        }

        return trim($firstName . ' ' . ($lastName ?? ''));
    }

    private function notFound(string $message): Result
    {
        return Result::failure(new Error(HrErrorCode::NOT_FOUND, $message));
    }

    private function failure(string $message): Result
    {
        return Result::failure(new Error(HrErrorCode::INVALID_VALUE, $message));
    }
}
