<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\VehicleService\DTOs\VehicleServiceInspectionData;
use Modules\VehicleService\DTOs\VehicleServiceJobData;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;
use Modules\VehicleService\Models\VehicleServiceJob;

final class VehicleServiceJobService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly VehicleServiceNumberService $numbers,
        private readonly VehicleServiceValidationService $validator,
        private readonly VehicleServiceCommissionService $commissions,
        private readonly VehicleServiceStatusService $statuses,
        private readonly VehicleServiceInspectionService $inspections,
    ) {}

    public function create(VehicleServiceJobData $data): VehicleServiceJob
    {
        return DB::transaction(function () use ($data): VehicleServiceJob {
            $this->validator->customer($data->tenantId, $data->organizationUnitId, $data->customerId);
            $this->validator->vehicle($data->tenantId, $data->organizationUnitId, $data->vehicleId, $data->customerId);
            if ($data->supervisorEmployeeId !== null) {
                $this->validator->employee($data->tenantId, $data->organizationUnitId, $data->supervisorEmployeeId);
            }
            if ($data->odometerReading !== null) {
                $this->validator->nonNegative($data->odometerReading, 'Odometer reading cannot be negative.');
            }

            $job = VehicleServiceJob::query()->create($this->attributes($data, true, '0.000000'));
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

    public function update(VehicleServiceJob $job, VehicleServiceJobData $data): VehicleServiceJob
    {
        $this->validator->assertMutable($job);

        return DB::transaction(function () use ($job, $data): VehicleServiceJob {
            if ((int) $job->tenant_id !== $data->tenantId || $job->organization_unit_id !== $data->organizationUnitId) {
                throw new InvalidArgumentException('Service job scope cannot be changed.');
            }
            $this->validator->customer($data->tenantId, $data->organizationUnitId, $data->customerId);
            $this->validator->vehicle($data->tenantId, $data->organizationUnitId, $data->vehicleId, $data->customerId);
            if ($data->supervisorEmployeeId !== null) {
                $this->validator->employee($data->tenantId, $data->organizationUnitId, $data->supervisorEmployeeId);
            }
            if ($data->odometerReading !== null) {
                $this->validator->nonNegative($data->odometerReading, 'Odometer reading cannot be negative.');
            }

            $job->fill($this->attributes($data, false, (string) $job->grand_total))->save();

            return $job->refresh()->load($this->relations());
        });
    }

    public function delete(VehicleServiceJob $job): void
    {
        if ($job->status !== VehicleServiceJobStatus::Draft
            || $job->invoiceLinks()->exists()
            || $job->lines()->whereNotNull('inventory_movement_id')->exists()) {
            throw new InvalidArgumentException('Only uninvoiced draft service jobs can be deleted.');
        }
        $job->delete();
    }

    /** @return array<string, mixed> */
    private function attributes(VehicleServiceJobData $data, bool $creating, string $commissionBase): array
    {
        $attributes = [
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'job_date' => $data->jobDate,
            'expected_delivery_date' => $data->expectedDeliveryDate,
            'customer_id' => $data->customerId,
            'vehicle_id' => $data->vehicleId,
            'supervisor_employee_id' => $data->supervisorEmployeeId,
            'supervisor_commission_type' => $data->supervisorCommissionType->value,
            'supervisor_commission_value' => $this->math->normalize($data->supervisorCommissionValue),
            'supervisor_commission_amount' => $this->commissions->calculate(
                $data->supervisorCommissionType,
                $data->supervisorCommissionValue,
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
                'subtotal' => '0.000000',
                'discount_total' => '0.000000',
                'tax_total' => '0.000000',
                'charge_total' => '0.000000',
                'grand_total' => '0.000000',
                'created_by' => $data->createdBy,
            ];
        }

        return $attributes;
    }

    /** @return list<string> */
    public function relations(): array
    {
        return [
            'customer', 'vehicle.make', 'vehicle.model', 'vehicle.currentOwnerships', 'supervisor', 'inspection.inspector',
            'invoiceLinks.invoice.balance', 'paymentLinks.payment.lines.paymentMethod', 'paymentLinks.payment.lines.internalBankAccount', 'paymentLinks.payment.allocations', 'paymentLinks.invoice',
        ];
    }
}
