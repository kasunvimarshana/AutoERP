<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\VehicleService\DTOs\VehicleServiceInspectionData;
use Modules\VehicleService\DTOs\VehicleServiceJobData;
use Modules\VehicleService\Enums\VehicleServiceCommissionType;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;
use Modules\VehicleService\Models\VehicleServiceInspection;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Services\Concerns\AssertsVehicleServiceExpectedVersion;

final class VehicleServiceJobService
{
    use AssertsVehicleServiceExpectedVersion;

    private const ZERO_AMOUNT = '0.000000';

    public function __construct(
        private readonly DecimalMath $math,
        private readonly VehicleServiceNumberService $numbers,
        private readonly VehicleServiceValidationService $validator,
        private readonly VehicleServiceCommissionService $commissions,
        private readonly VehicleServiceCommissionPolicyService $commissionPolicies,
        private readonly VehicleServiceStatusService $statuses,
        private readonly VehicleServiceInspectionService $inspections,
    ) {}

    public function create(VehicleServiceJobData $data): VehicleServiceJob
    {
        return DB::transaction(function () use ($data): VehicleServiceJob {
            $this->validator->customer($data->tenantId, $data->organizationUnitId, $data->customerId);
            $this->validator->customer($data->tenantId, $data->organizationUnitId, $this->billToCustomerId($data));
            $this->validator->vehicle($data->tenantId, $data->organizationUnitId, $data->vehicleId, $data->customerId);
            if ($data->supervisorEmployeeId !== null) {
                $this->validator->employee($data->tenantId, $data->organizationUnitId, $data->supervisorEmployeeId);
            }
            if ($data->odometerReading !== null) {
                $this->validator->nonNegative($data->odometerReading, 'Odometer reading cannot be negative.');
            }

            $job = new VehicleServiceJob();
            $job->forceFill($this->attributes($data, true, self::ZERO_AMOUNT))->save();
            $this->statuses->recordCreated($job, $data->createdBy);
            if ($data->customerComplaint !== null) {
                $this->inspections->save($job, new VehicleServiceInspectionData(
                    customerComplaint: $data->customerComplaint,
                    odometerReading: $data->odometerReading,
                    fuelLevel: $data->fuelLevel,
                ));
            }

            return $job->load($this->relations());
        });
    }

    public function update(VehicleServiceJob $job, VehicleServiceJobData $data, ?int $expectedVersion = null): VehicleServiceJob
    {
        return DB::transaction(function () use ($job, $data, $expectedVersion): VehicleServiceJob {
            $job = VehicleServiceJob::query()->lockForUpdate()->findOrFail($job->getKey());
            $this->assertExpectedVersion($job, $expectedVersion);
            $this->validator->assertMutable($job);

            if ((int) $job->tenant_id !== $data->tenantId || $job->organization_unit_id !== $data->organizationUnitId) {
                throw new InvalidArgumentException('Service job scope cannot be changed.');
            }
            $this->validator->customer($data->tenantId, $data->organizationUnitId, $data->customerId);
            $this->validator->customer($data->tenantId, $data->organizationUnitId, $this->billToCustomerId($data));
            $this->validator->vehicle($data->tenantId, $data->organizationUnitId, $data->vehicleId, $data->customerId);
            if ($data->supervisorEmployeeId !== null) {
                $this->validator->employee($data->tenantId, $data->organizationUnitId, $data->supervisorEmployeeId);
            }
            if ($data->odometerReading !== null) {
                $this->validator->nonNegative($data->odometerReading, 'Odometer reading cannot be negative.');
            }

            $versionBefore = (int) $job->row_version;
            $job->forceFill($this->attributes($data, false, (string) $job->grand_total, $job))->save();
            $job->refresh()->load('inspection');
            $complaintChanged = $this->syncCustomerComplaint($job, $data);
            if ($complaintChanged && (int) $job->row_version === $versionBefore) {
                $job = $this->bumpJobVersion($job);
            }

            return $job->refresh()->load($this->relations());
        });
    }

    public function delete(VehicleServiceJob $job, ?int $expectedVersion = null): void
    {
        DB::transaction(function () use ($job, $expectedVersion): void {
            $job = VehicleServiceJob::query()->lockForUpdate()->findOrFail($job->getKey());
            $this->assertExpectedVersion($job, $expectedVersion);

            if ($job->status !== VehicleServiceJobStatus::Draft
                || $job->invoiceLinks()->exists()
                || $job->lines()->whereNotNull('inventory_movement_id')->exists()) {
                throw new InvalidArgumentException('Only uninvoiced draft service jobs can be deleted.');
            }
            $job->delete();
        });
    }

    /** @return array<string, mixed> */
    private function attributes(
        VehicleServiceJobData $data,
        bool $creating,
        string $commissionBase,
        ?VehicleServiceJob $existing = null,
    ): array {
        $commission = $this->supervisorCommission($data, $existing);
        $attributes = [
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'job_date' => $data->jobDate,
            'expected_delivery_date' => $data->expectedDeliveryDate,
            'type' => $data->type->value,
            'customer_id' => $data->customerId,
            'bill_to_customer_id' => $this->billToCustomerId($data),
            'vehicle_id' => $data->vehicleId,
            'supervisor_employee_id' => $data->supervisorEmployeeId,
            'supervisor_commission_type' => $commission['type']->value,
            'supervisor_commission_value' => $commission['value'],
            'supervisor_commission_amount' => $this->commissions->calculate(
                $commission['type'],
                $commission['value'],
                $commissionBase,
            ),
            'odometer_reading' => $data->odometerReading === null ? null : $this->math->normalize($data->odometerReading),
            'fuel_level' => $data->fuelLevel,
            'priority' => $data->priority,
            'notes' => $data->notes,
        ];

        if ($creating) {
            $attributes += [
                'job_number' => $data->jobNumber ?? $this->numbers->next($data->tenantId),
                'status' => VehicleServiceJobStatus::Draft->value,
                'subtotal' => self::ZERO_AMOUNT,
                'discount_total' => self::ZERO_AMOUNT,
                'tax_total' => self::ZERO_AMOUNT,
                'charge_total' => self::ZERO_AMOUNT,
                'grand_total' => self::ZERO_AMOUNT,
                'created_by' => $data->createdBy,
            ];
        }

        return $attributes;
    }

    /** @return array{type: VehicleServiceCommissionType, value: string} */
    private function supervisorCommission(
        VehicleServiceJobData $data,
        ?VehicleServiceJob $existing,
    ): array {
        if ($data->supervisorEmployeeId === null) {
            return ['type' => VehicleServiceCommissionType::None, 'value' => self::ZERO_AMOUNT];
        }

        if ($data->supervisorCommissionType !== null) {
            if ($data->supervisorCommissionValue === null) {
                throw new InvalidArgumentException('Supervisor commission value is required.');
            }

            return [
                'type' => $data->supervisorCommissionType,
                'value' => $this->math->normalize($data->supervisorCommissionValue),
            ];
        }

        if ($data->supervisorCommissionValue !== null) {
            throw new InvalidArgumentException('Supervisor commission type is required.');
        }

        if ($existing instanceof VehicleServiceJob && $existing->supervisor_employee_id !== null) {
            return [
                'type' => $existing->supervisor_commission_type,
                'value' => (string) $existing->supervisor_commission_value,
            ];
        }

        if ($data->organizationUnitId === null) {
            return ['type' => VehicleServiceCommissionType::None, 'value' => self::ZERO_AMOUNT];
        }

        return $this->commissionPolicies->resolveSupervisorDefault(
            $data->tenantId,
            $data->organizationUnitId,
        );
    }

    private function billToCustomerId(VehicleServiceJobData $data): int
    {
        return $data->billToCustomerId ?? $data->customerId;
    }

    private function syncCustomerComplaint(VehicleServiceJob $job, VehicleServiceJobData $data): bool
    {
        if (! $data->customerComplaintProvided) {
            return false;
        }

        $current = $job->inspection?->customer_complaint;
        if ($current === $data->customerComplaint) {
            return false;
        }

        if ($job->inspection instanceof VehicleServiceInspection) {
            $job->inspection->forceFill([
                'customer_complaint' => $data->customerComplaint,
            ])->save();

            return true;
        }

        if ($data->customerComplaint === null) {
            return false;
        }

        VehicleServiceInspection::query()->create([
            'tenant_id' => $job->tenant_id,
            'organization_unit_id' => $job->organization_unit_id,
            'vehicle_service_job_id' => $job->getKey(),
            'customer_complaint' => $data->customerComplaint,
        ]);

        return true;
    }

    /** @return list<string> */
    public function relations(): array
    {
        return [
            'customer', 'billToCustomer', 'vehicle.make', 'vehicle.model', 'vehicle.currentOwnerships', 'supervisor', 'inspection.inspector',
            'invoiceLinks.invoice.balance', 'paymentLinks.payment.lines.paymentMethod', 'paymentLinks.payment.allocations', 'paymentLinks.invoice',
        ];
    }
}
