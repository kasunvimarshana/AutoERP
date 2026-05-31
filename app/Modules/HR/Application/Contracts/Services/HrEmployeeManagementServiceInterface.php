<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface HrEmployeeManagementServiceInterface
{
    public function listEmployees(array $filters, int $perPage, int $page): Result;

    public function getEmployee(int|string $id): Result;

    public function createEmployee(array $payload): Result;

    public function updateEmployee(int|string $id, array $payload): Result;

    public function changeEmployeeStatus(int|string $id, string $toStatus, ?string $reason = null): Result;

    public function safeDeleteEmployee(int|string $id): Result;

    public function listDepartments(array $filters, int $perPage, int $page): Result;

    public function getDepartment(int|string $id): Result;

    public function createDepartment(array $payload): Result;

    public function updateDepartment(int|string $id, array $payload): Result;

    public function listDesignations(array $filters, int $perPage, int $page): Result;

    public function getDesignation(int|string $id): Result;

    public function createDesignation(array $payload): Result;

    public function updateDesignation(int|string $id, array $payload): Result;

    public function listEmploymentTypes(array $filters, int $perPage, int $page): Result;

    public function getEmploymentType(int|string $id): Result;

    public function createEmploymentType(array $payload): Result;

    public function updateEmploymentType(int|string $id, array $payload): Result;

    public function listEmployeeContacts(int|string $employeeId): Result;

    public function createEmployeeContact(int|string $employeeId, array $payload): Result;

    public function updateEmployeeContact(int|string $employeeId, int|string $contactId, array $payload): Result;

    public function deactivateEmployeeContact(int|string $employeeId, int|string $contactId): Result;

    public function listEmployeeAddresses(int|string $employeeId): Result;

    public function createEmployeeAddress(int|string $employeeId, array $payload): Result;

    public function updateEmployeeAddress(int|string $employeeId, int|string $addressId, array $payload): Result;

    public function deactivateEmployeeAddress(int|string $employeeId, int|string $addressId): Result;

    public function getEmploymentDetails(int|string $employeeId): Result;

    public function updateEmploymentDetails(int|string $employeeId, array $payload): Result;

    public function listEmployeeUserAccounts(int|string $employeeId): Result;

    public function createEmployeeUserAccess(int|string $employeeId, array $payload): Result;

    public function linkExistingUserToEmployee(int|string $employeeId, array $payload): Result;

    public function deactivateEmployeeUserAccess(int|string $employeeId, int|string $accessId, array $payload): Result;

    public function unlinkEmployeeUserAccess(int|string $employeeId, int|string $accessId): Result;

    public function lookupEmployees(string $search, int $limit = 20): Result;

    public function listActiveEmployees(int $limit = 50): Result;

    public function validateEmployeeForAssignmentContext(int|string $employeeId, string $assignmentContext): Result;

    public function getEmployeesByDepartment(int|string $departmentId): Result;

    public function getEmployeesByDesignation(int|string $designationId): Result;
}
