<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Customer\Models\Customer;
use Modules\Hr\Enums\EmployeeStatus;
use Modules\Hr\Models\HrEmployee;
use Modules\Item\Enums\ItemType;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleService\Enums\VehicleServiceLineSourceType;
use Modules\VehicleService\Enums\VehicleServiceDesignationCode;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobLine;

final class VehicleServiceValidationService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function customer(int $tenantId, ?int $organizationUnitId, int $customerId): Customer
    {
        /** @var Customer $customer */
        $customer = $this->scoped(Customer::query(), $tenantId, $organizationUnitId)->findOrFail($customerId);

        return $customer;
    }

    public function vehicle(int $tenantId, ?int $organizationUnitId, int $vehicleId, int $customerId): Vehicle
    {
        /** @var Vehicle $vehicle */
        $vehicle = $this->scoped(Vehicle::query()->with('currentOwnerships'), $tenantId, $organizationUnitId)->findOrFail($vehicleId);
        if (! in_array($vehicle->status, [VehicleStatus::Active, VehicleStatus::UnderService], true)) {
            throw new InvalidArgumentException('Inactive or unavailable vehicles cannot be used for service jobs.');
        }
        $customerRelationship = $vehicle->currentOwnerships->first(fn ($ownership): bool => ($ownership->owner_type instanceof \BackedEnum ? $ownership->owner_type->value : $ownership->owner_type) === 'customer');
        if ($customerRelationship !== null && (int) $customerRelationship->owner_id !== $customerId) {
            throw new InvalidArgumentException('Vehicle does not belong to the selected customer.');
        }

        return $vehicle;
    }

    public function employee(int $tenantId, ?int $organizationUnitId, int $employeeId): HrEmployee
    {
        /** @var HrEmployee $employee */
        $employee = $this->scoped(HrEmployee::query(), $tenantId, $organizationUnitId)->findOrFail($employeeId);
        if ($employee->status !== EmployeeStatus::Active) {
            throw new InvalidArgumentException('Only active employees can be assigned to service jobs.');
        }

        return $employee;
    }

    public function supervisorEmployee(int $tenantId, ?int $organizationUnitId, int $employeeId): HrEmployee
    {
        $employee = $this->employee($tenantId, $organizationUnitId, $employeeId);
        $employee->loadMissing('designation');
        if ($employee->designation?->code !== VehicleServiceDesignationCode::Supervisor->value) {
            throw new InvalidArgumentException('Only employees with the Supervisor designation can supervise service jobs.');
        }

        return $employee;
    }

    public function workforceEmployee(
        VehicleServiceJob $job,
        VehicleServiceJobLine $line,
        int $employeeId,
    ): HrEmployee {
        $employee = $this->employee(
            (int) $job->tenant_id,
            $job->organization_unit_id,
            $employeeId,
        );
        $employee->loadMissing('designation');
        if ($employee->designation === null) {
            throw new InvalidArgumentException('Assign a designation to the employee before adding them to service workforce.');
        }

        if ($line->uses_job_supervisor) {
            if ($job->supervisor_employee_id === null
                || (int) $job->supervisor_employee_id !== $employeeId
                || $employee->designation->code !== VehicleServiceDesignationCode::Supervisor->value) {
                throw new InvalidArgumentException('This labour line must use the supervisor selected on the service job.');
            }

            return $employee;
        }

        if ($employee->designation->code === VehicleServiceDesignationCode::Supervisor->value) {
            throw new InvalidArgumentException('Supervisor employees can only be assigned to job-supervisor labour lines.');
        }

        return $employee;
    }

    public function assertEmployeeAssignable(VehicleServiceJobLine $line): void
    {
        $line->loadMissing('item');
        $assignable = in_array($line->line_source_type, [
            VehicleServiceLineSourceType::ServiceItem,
            VehicleServiceLineSourceType::LabourItem,
        ], true);

        if ($line->line_source_type === VehicleServiceLineSourceType::ComboChild) {
            $assignable = in_array($line->item?->item_type, [ItemType::Service, ItemType::Labour], true);
        }

        if (! $assignable || ! $line->is_employee_assignable) {
            throw new InvalidArgumentException('Employees can only be assigned to service or labour lines.');
        }
    }

    public function assertInventoryIssueLine(VehicleServiceJobLine $line): void
    {
        $line->loadMissing('item');
        $eligibleType = $line->line_source_type === VehicleServiceLineSourceType::InventoryItem
            || ($line->line_source_type === VehicleServiceLineSourceType::ComboChild && (bool) $line->item?->is_stockable);
        if (! $eligibleType || ! $line->is_inventory_tracked || $line->is_customer_supplied || $line->is_external) {
            throw new InvalidArgumentException('Only inventory item lines can create stock issues.');
        }
    }

    public function assertMutable(VehicleServiceJob $job): void
    {
        if (! in_array($job->status->value, ['draft', 'inspected', 'in_progress'], true)) {
            throw new InvalidArgumentException('This service job can no longer be edited.');
        }
    }

    public function nonNegative(string $value, string $message): void
    {
        if ($this->math->isNegative($value)) {
            throw new InvalidArgumentException($message);
        }
    }

    private function scoped($query, int $tenantId, ?int $organizationUnitId)
    {
        $query->where('tenant_id', $tenantId);

        return $organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where(function ($scope) use ($organizationUnitId): void {
                $scope->whereNull('organization_unit_id')->orWhere('organization_unit_id', $organizationUnitId);
            });
    }
}
