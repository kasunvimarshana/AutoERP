<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Core\Services\DecimalMath;
use Modules\HR\Models\HrEmployee;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Constants\OperationalFields;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementAdjustmentCategory;
use Modules\VehicleRental\Enums\AgreementAdjustmentStatus;
use Modules\VehicleRental\Enums\AgreementAdjustmentType;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\BaseRentPolicy;
use Modules\VehicleRental\Enums\ReassignmentFinancialStatus;
use Modules\VehicleRental\Enums\ReassignmentType;
use Modules\VehicleRental\Enums\VehicleUseAction;
use Modules\VehicleRental\Enums\VehicleUseStatus;
use Modules\VehicleRental\Models\AgreementAdjustment;
use Modules\VehicleRental\Models\AgreementAdjustmentHistory;
use Modules\VehicleRental\Models\CustomerAgreement;
use Modules\VehicleRental\Models\OwnerAgreement;
use Modules\VehicleRental\Models\RunningChart;
use Modules\VehicleRental\Models\VehicleRentalReassignment;
use Modules\VehicleRental\Models\VehicleUse;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class VehicleDriverReassignmentService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly RentalAuthorization $authorization,
        private readonly VehicleUseService $vehicleUses,
        private readonly BaseRentPreview $baseRentPreview,
    ) {}

    public function reassignRunningChartDriver(
        AgreementContext $context,
        int $chartId,
        int $newDriverEmployeeId,
        string $reason,
        int $expectedVersion
    ): VehicleRentalReassignment {
        $this->authorization->assertChart($context, \Modules\VehicleRental\Enums\RunningChartAction::Update, true);

        return DB::transaction(function () use ($context, $chartId, $newDriverEmployeeId, $reason, $expectedVersion): VehicleRentalReassignment {
            $chart = RunningChart::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->lockForUpdate()
                ->findOrFail($chartId);

            if ((int) $chart->row_version !== $expectedVersion) {
                throw new ConflictHttpException("Running chart version mismatch: expected {$expectedVersion}, currently {$chart->row_version}.");
            }

            $driver = HrEmployee::query()
                ->where('tenant_id', $context->tenantId)
                ->findOrFail($newDriverEmployeeId);

            // Assert driver is not on overlapping running chart
            $hasOverlap = RunningChart::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->where('id', '!=', $chart->id)
                ->where('driver_employee_id', $driver->id)
                ->where('starts_at', '<', $chart->ends_at)
                ->where('ends_at', '>', $chart->starts_at)
                ->exists();

            if ($hasOverlap) {
                throw ValidationException::withMessages(['driver_employee_id' => ['The selected driver is already assigned to an overlapping running chart.']]);
            }

            $prevDriverId = $chart->driver_employee_id;

            $chart->driver_identity_source = \Modules\VehicleRental\Enums\DriverIdentitySource::Employee;
            $chart->driver_employee_id = $driver->id;
            $chart->driver_name_snapshot = $driver->display_name;
            $chart->driver_reference_snapshot = $driver->employee_number ?? $driver->code;
            $chart->driver_observation = trim($reason);
            $chart->row_version += 1;
            $chart->save();

            $reassignment = new VehicleRentalReassignment();
            $reassignment->tenant_id = $context->tenantId;
            $reassignment->reassignment_type = ReassignmentType::DriverReassignment;
            $reassignment->customer_agreement_id = $chart->vehicleUse?->customer_agreement_id;
            $reassignment->vehicle_use_id = $chart->vehicle_use_id;
            $reassignment->running_chart_id = $chart->id;
            $reassignment->previous_driver_employee_id = $prevDriverId;
            $reassignment->new_driver_employee_id = $driver->id;
            $reassignment->financial_status = ReassignmentFinancialStatus::None;
            $reassignment->financial_delta_amount = '0.000000';
            $reassignment->reason = trim($reason);
            $reassignment->occurred_at = now();
            $reassignment->user_id = $context->actorId;
            $reassignment->save();

            return $reassignment->load(['previousDriver', 'newDriver', 'runningChart']);
        });
    }

    public function replaceVehicle(
        AgreementContext $context,
        int $currentUseId,
        array $input,
        int $expectedVersion
    ): VehicleRentalReassignment {
        $this->authorization->assertUse($context, true);

        return DB::transaction(function () use ($context, $currentUseId, $input, $expectedVersion): VehicleRentalReassignment {
            $currentUse = VehicleUse::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->lockForUpdate()
                ->findOrFail($currentUseId);

            if ((int) $currentUse->row_version !== $expectedVersion) {
                throw new ConflictHttpException("Vehicle use version mismatch: expected {$expectedVersion}, currently {$currentUse->row_version}.");
            }

            $cutoverAt = (string) ($input['cutover_at'] ?? now()->toDateTimeString());
            $returnOdometer = (string) ($input['return_odometer'] ?? $currentUse->handover_odometer ?? '0');
            $reason = (string) ($input['reason'] ?? 'Vehicle breakdown or operational swap');
            $newVehicleId = (int) $input['new_vehicle_id'];
            $newOwnerAgreementId = ! empty($input['new_owner_agreement_id']) ? (int) $input['new_owner_agreement_id'] : null;

            // 1. Transition previous use to Returned
            if ($currentUse->status === VehicleUseStatus::InCustody) {
                $this->vehicleUses->transition($context, $currentUse->id, (int) $currentUse->row_version, VehicleUseAction::ReturnVehicle, [
                    'occurred_at' => $cutoverAt,
                    'odometer' => $returnOdometer,
                    'reason' => $reason,
                ]);
                $currentUse->refresh();
            }

            // 2. Plan replacement vehicle use
            $customerAgreement = $currentUse->customerAgreement()->lockForUpdate()->first();
            $customerVersion = (int) ($customerAgreement?->row_version ?? AgreementFields::INITIAL_VERSION);

            $replacementUse = $this->vehicleUses->plan($context, (int) $currentUse->customer_agreement_id, $customerVersion, [
                'vehicle_id' => $newVehicleId,
                'owner_agreement_id' => $newOwnerAgreementId,
                'starts_at' => $cutoverAt,
                'ends_at' => $currentUse->ends_at_input,
                'notes' => 'Replacement for Use #'.$currentUse->id.' · '.$reason,
            ], $currentUse->id);

            // 3. Automatically Handover the replacement vehicle use
            $this->vehicleUses->transition($context, $replacementUse->id, (int) $replacementUse->row_version, VehicleUseAction::Handover, [
                'occurred_at' => $cutoverAt,
                'odometer' => (string) ($input['new_handover_odometer'] ?? '0'),
                'reason' => 'Replacement handover · '.$reason,
            ]);

            // 4. Financial Rebalancing if owner agreement was involved
            $financialDelta = '0.000000';
            $financialStatus = ReassignmentFinancialStatus::None;

            if ($currentUse->owner_agreement_id !== null) {
                $ownerAgreement = OwnerAgreement::query()
                    ->forContext($context->tenantId, $context->organizationUnitId)
                    ->find($currentUse->owner_agreement_id);

                if ($ownerAgreement !== null) {
                    // Calculate pro-rata adjustment for unearned owner base rent
                    $cutoverDate = CarbonImmutable::parse($cutoverAt)->toDateString();
                    $startsOn = $ownerAgreement->starts_on?->toDateString() ?? $cutoverDate;

                    if ($ownerAgreement->ends_on !== null && CarbonImmutable::parse($cutoverDate)->lessThan($ownerAgreement->ends_on)) {
                        $unearnedPreview = $this->baseRentPreview->calculate(
                            AgreementKind::Owner,
                            $context,
                            $ownerAgreement->id,
                            [
                                'policy' => BaseRentPolicy::ActualCalendarDays->value,
                                'expected_version' => (int) $ownerAgreement->row_version,
                                'from' => $cutoverDate,
                                'until' => $ownerAgreement->ends_on->toDateString(),
                            ]
                        );
                        $unearnedAmount = (string) ($unearnedPreview['base_rent'] ?? $unearnedPreview['amount'] ?? '0.000000');

                        if ($this->math->compare($unearnedAmount, '0.000000') > 0) {
                            $financialDelta = $unearnedAmount;
                            $financialStatus = ReassignmentFinancialStatus::Pending;

                            // Create an Agreement Adjustment for unearned owner compensation
                            $adjustment = new AgreementAdjustment();
                            $adjustment->tenant_id = $context->tenantId;
                            $adjustment->organization_unit_id = $context->organizationUnitId;
                            $adjustment->owner_agreement_id = $ownerAgreement->id;
                            $adjustment->adjustment_type = AgreementAdjustmentType::CreditNote;
                            $adjustment->category = AgreementAdjustmentCategory::DowntimeCompensation;
                            $adjustment->status = AgreementAdjustmentStatus::Authorized;
                            $adjustment->amount = $unearnedAmount;
                            $adjustment->original_amount = $unearnedAmount;
                            $adjustment->tax_amount = '0.000000';
                            $adjustment->reason = 'Pro-rata lessor unearned rent deduction due to vehicle replacement at '.$cutoverAt;
                            $adjustment->actor_id = $context->actorId;
                            $adjustment->authorized_by_user_id = $context->actorId;
                            $adjustment->authorized_at = now();
                            $adjustment->row_version = AgreementFields::INITIAL_VERSION;
                            $adjustment->save();
                        }
                    }
                }
            }

            // 5. Record Reassignment audit trail
            $reassignment = new VehicleRentalReassignment();
            $reassignment->tenant_id = $context->tenantId;
            $reassignment->reassignment_type = ReassignmentType::VehicleReplacement;
            $reassignment->customer_agreement_id = $currentUse->customer_agreement_id;
            $reassignment->vehicle_use_id = $replacementUse->id;
            $reassignment->previous_vehicle_id = $currentUse->vehicle_id;
            $reassignment->new_vehicle_id = $newVehicleId;
            $reassignment->previous_owner_agreement_id = $currentUse->owner_agreement_id;
            $reassignment->new_owner_agreement_id = $newOwnerAgreementId;
            $reassignment->financial_status = $financialStatus;
            $reassignment->financial_delta_amount = $financialDelta;
            $reassignment->reason = trim($reason);
            $reassignment->occurred_at = CarbonImmutable::parse($cutoverAt);
            $reassignment->user_id = $context->actorId;
            $reassignment->save();

            return $reassignment->load(['previousVehicle', 'newVehicle', 'previousOwnerAgreement', 'newOwnerAgreement', 'vehicleUse']);
        });
    }

    public function listReassignments(AgreementContext $context, ?int $customerAgreementId = null): Collection
    {
        $this->authorization->assertUse($context, false);

        return VehicleRentalReassignment::query()
            ->forContext($context->tenantId)
            ->when($customerAgreementId !== null, fn ($q) => $q->where('customer_agreement_id', $customerAgreementId))
            ->with(['previousVehicle', 'newVehicle', 'previousDriver', 'newDriver', 'previousOwnerAgreement', 'newOwnerAgreement', 'user'])
            ->orderByDesc('occurred_at')
            ->get();
    }
}
