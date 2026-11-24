<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\Enums\FinanceAccountRoleCode;
use Modules\Finance\Enums\FinancePostingProfileCode;
use Modules\HR\Models\HrEmployee;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\DTOs\InvoiceSourceData;
use Modules\Invoice\DTOs\InvoiceSourceLineData;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceLineType;
use Modules\Invoice\Enums\InvoicePartyType;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\InvoiceBalanceUpdater;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\Invoice\Services\InvoiceStatusService;
use Modules\Invoice\Services\TaxedSourceInvoiceFactory;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\Enums\AllocationStatus;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentAllocation;
use Modules\Payment\Services\PaymentAllocationReversalService;
use Modules\Payment\Services\PaymentAllocationService;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AgreementAdjustmentCategory;
use Modules\VehicleRental\Enums\AgreementAdjustmentStatus;
use Modules\VehicleRental\Enums\AgreementAdjustmentType;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\AirConditioningMode;
use Modules\VehicleRental\Enums\BaseRentPolicy;
use Modules\VehicleRental\Enums\BillingCycleItemCategory;
use Modules\VehicleRental\Enums\BillingCycleItemType;
use Modules\VehicleRental\Enums\BillingCycleMode;
use Modules\VehicleRental\Enums\BillingCycleStatus;
use Modules\VehicleRental\Enums\BillingFinalizationMode;
use Modules\VehicleRental\Enums\RentalBasis;
use Modules\VehicleRental\Enums\RentalExpenseStatus;
use Modules\VehicleRental\Enums\RentalExpenseType;
use Modules\VehicleRental\Enums\RunningChartStatus;
use Modules\VehicleRental\Enums\VehicleUseStatus;
use Modules\VehicleRental\Models\AgreementAdjustment;
use Modules\VehicleRental\Models\AgreementAdjustmentHistory;
use Modules\VehicleRental\Models\BillingCycle;
use Modules\VehicleRental\Models\BillingCycleChart;
use Modules\VehicleRental\Models\BillingCycleItem;
use Modules\VehicleRental\Models\CustomerAgreement;
use Modules\VehicleRental\Models\OwnerAgreement;
use Modules\VehicleRental\Models\RentalExpense;
use Modules\VehicleRental\Models\RunningChart;
use Modules\VehicleRental\Models\VehicleRentalReassignment;
use Modules\VehicleRental\Models\VehicleUse;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class TwoStageInvoicingService
{
    private const WHOLE_CHARGE = '1.000000';

    public function __construct(
        private readonly DecimalMath $math,
        private readonly RentalAuthorization $authorization,
        private readonly BaseRentPreview $baseRentPreview,
        private readonly TaxedSourceInvoiceFactory $invoiceFactory,
        private readonly InvoiceCreationService $invoiceService,
        private readonly InvoiceStatusService $invoiceStatusService,
        private readonly InvoiceBalanceUpdater $balanceUpdater,
        private readonly PaymentAllocationService $allocationService,
        private readonly PaymentAllocationReversalService $allocationReversalService,
        private readonly BillingCycleNumberService $cycleNumberService,
    ) {}

    public function createCycle(
        AgreementKind $kind,
        AgreementContext $context,
        int $agreementId,
        array $input
    ): BillingCycle {
        $this->authorization->assertBilling($context, $kind);

        $periodFrom = (string) ($input['period_from'] ?? '');
        $periodUntil = (string) ($input['period_until'] ?? '');
        if ($periodFrom === '' || $periodUntil === '') {
            throw ValidationException::withMessages(['period' => ['Specify both period from and period until.']]);
        }

        $from = CarbonImmutable::parse($periodFrom)->startOfDay();
        $until = CarbonImmutable::parse($periodUntil)->endOfDay();
        if ($until->lessThan($from)) {
            throw ValidationException::withMessages(['period_until' => ['Period until must be on or after period from.']]);
        }

        return DB::transaction(function () use ($kind, $context, $agreementId, $input, $periodFrom, $periodUntil): BillingCycle {
            $customer = $kind === AgreementKind::Customer;
            $agreement = $customer
                ? CustomerAgreement::query()->forContext($context->tenantId, $context->organizationUnitId)->lockForUpdate()->findOrFail($agreementId)
                : OwnerAgreement::query()->forContext($context->tenantId, $context->organizationUnitId)->lockForUpdate()->findOrFail($agreementId);

            $foreignCol = $customer ? 'customer_agreement_id' : 'owner_agreement_id';
            $overlapExists = BillingCycle::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->where($foreignCol, $agreement->id)
                ->where('status', '!=', BillingCycleStatus::Cancelled)
                ->where('period_from', '<=', $periodUntil)
                ->where('period_until', '>=', $periodFrom)
                ->exists();

            if ($overlapExists) {
                throw ValidationException::withMessages(['period' => ['A billing cycle already covers an overlapping period for this agreement.']]);
            }

            $rawBillingMode = (string) ($input['billing_mode'] ?? 'without_running_chart');
            $billingMode = match ($rawBillingMode) {
                'with_running_chart' => BillingCycleMode::WithRunningChart,
                default => BillingCycleMode::WithoutRunningChart,
            };

            $rawFinalMode = (string) ($input['finalization_mode'] ?? 'supplementary_invoice');
            $finalizationMode = match ($rawFinalMode) {
                'consolidated', 'consolidated_revision' => BillingFinalizationMode::ConsolidatedRevision,
                default => BillingFinalizationMode::SupplementaryInvoice,
            };

            $preview = $this->baseRentPreview->calculate(
                $kind,
                $context,
                $agreement->id,
                [
                    'policy' => BaseRentPolicy::ActualCalendarDays->value,
                    'expected_version' => (int) $agreement->row_version,
                    'from' => $periodFrom,
                    'until' => $periodUntil,
                ]
            );

            $baseRent = (string) ($preview['base_rent'] ?? $preview['amount'] ?? '0.000000');
            $exchangeRate = (string) ($input['exchange_rate'] ?? '1.000000');
            $cycleNumber = $this->cycleNumberService->next($context, $periodFrom);

            $cycle = new BillingCycle;
            $cycle->tenant_id = $context->tenantId;
            $cycle->organization_unit_id = $context->organizationUnitId;
            $cycle->cycle_number = $cycleNumber;
            if ($customer) {
                $cycle->customer_agreement_id = $agreement->id;
            } else {
                $cycle->owner_agreement_id = $agreement->id;
            }
            $cycle->period_from = $periodFrom;
            $cycle->period_until = $periodUntil;
            $cycle->status = BillingCycleStatus::Draft;
            $cycle->billing_mode = $billingMode;
            $cycle->finalization_mode = $finalizationMode;
            $cycle->base_rent_amount = $baseRent;
            $cycle->net_subtotal = $baseRent;
            $cycle->tax_amount = '0.000000';
            $cycle->total_billed_amount = $baseRent;
            $cycle->total_paid_amount = '0.000000';
            $cycle->currency_id = $agreement->currency_id;
            $cycle->exchange_rate = $exchangeRate;
            $cycle->notes = $input['notes'] ?? null;
            $cycle->created_by = $context->actorId;
            $cycle->row_version = AgreementFields::INITIAL_VERSION;
            $cycle->save();

            $issueInitial = ! empty($input['issue_initial_invoice']);
            if ($issueInitial && $this->math->compare($baseRent, '0.000000') > 0) {
                $invoice = $this->issueStage1Invoice($kind, $context, $agreement, $cycle, $baseRent, $exchangeRate, $input);
                $cycle->initial_invoice_id = $invoice->id;
                $cycle->status = BillingCycleStatus::Stage1Generated;
                $cycle->save();
            }

            return $cycle->load(['customerAgreement', 'ownerAgreement', 'initialInvoice']);
        });
    }

    public function getCandidateRunningCharts(AgreementContext $context, int $cycleId): array
    {
        $cycle = BillingCycle::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->with(['charts'])
            ->findOrFail($cycleId);

        $kind = $cycle->customer_agreement_id !== null ? AgreementKind::Customer : AgreementKind::Owner;
        $this->authorization->assertBilling($context, $kind);

        $attachedChartIds = $cycle->charts->pluck('running_chart_id')->all();
        $agreementId = $cycle->customer_agreement_id ?? $cycle->owner_agreement_id;
        $foreignKey = $cycle->customer_agreement_id !== null ? 'customer_agreement_id' : 'owner_agreement_id';

        $query = RunningChart::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->where('status', RunningChartStatus::Finalized)
            ->whereHas('vehicleUse', fn ($q) => $q->where($foreignKey, $agreementId))
            ->where('starts_at', '<=', $cycle->period_until->endOfDay())
            ->where('ends_at', '>=', $cycle->period_from->startOfDay())
            ->with(['vehicleUse.vehicle']);

        $charts = $query->orderBy('starts_at')->get();

        $finalizedInOtherCycles = BillingCycleChart::query()
            ->where('tenant_id', $context->tenantId)
            ->where('billing_cycle_id', '!=', $cycle->id)
            ->whereIn('running_chart_id', $charts->pluck('id')->all())
            ->whereHas('billingCycle', function ($q) use ($cycle) {
                $q->where('status', BillingCycleStatus::Finalized);
                if ($cycle->customer_agreement_id !== null) {
                    $q->whereNotNull('customer_agreement_id');
                } else {
                    $q->whereNotNull('owner_agreement_id');
                }
            })
            ->pluck('running_chart_id')
            ->flip()
            ->all();

        $attachedInThisCycle = array_flip($attachedChartIds);

        return $charts->map(function (RunningChart $chart) use ($attachedInThisCycle, $finalizedInOtherCycles) {
            $vehicle = $chart->vehicleUse?->vehicle;
            $regNo = $vehicle?->registration_number ?? $vehicle?->vehicle_number ?? 'Unknown';
            $label = $vehicle ? trim("{$regNo} · {$vehicle->make} {$vehicle->model}") : $regNo;

            return [
                'id' => $chart->id,
                'reference' => $chart->reference,
                'starts_at' => $chart->starts_at?->toIso8601String(),
                'ends_at' => $chart->ends_at?->toIso8601String(),
                'commercial_km' => (string) ($chart->commercial_km ?? '0.000000'),
                'normal_ot_minutes' => (int) ($chart->normal_ot_minutes ?? 0),
                'double_ot_minutes' => (int) ($chart->double_ot_minutes ?? 0),
                'triple_ot_minutes' => (int) ($chart->triple_ot_minutes ?? 0),
                'night_outs' => (int) ($chart->night_outs ?? 0),
                'driver_name' => $chart->driver_name_snapshot ?? 'Assigned Driver',
                'driver_reference' => $chart->driver_reference_snapshot ?? '',
                'vehicle_id' => $chart->vehicleUse?->vehicle_id,
                'vehicle_number' => $regNo,
                'vehicle_label' => $label,
                'is_replacement' => $chart->vehicleUse?->replaces_use_id !== null,
                'is_attached' => isset($attachedInThisCycle[$chart->id]),
                'is_already_finalized' => isset($finalizedInOtherCycles[$chart->id]),
            ];
        })->all();
    }

    public function attachRunningCharts(
        AgreementContext $context,
        int $cycleId,
        array $chartIds,
        int $expectedVersion
    ): BillingCycle {
        return DB::transaction(function () use ($context, $cycleId, $chartIds, $expectedVersion): BillingCycle {
            $cycle = BillingCycle::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->lockForUpdate()
                ->findOrFail($cycleId);

            $this->assertCycleVersion($cycle, $expectedVersion);

            $kind = $cycle->customer_agreement_id !== null ? AgreementKind::Customer : AgreementKind::Owner;
            $this->authorization->assertBilling($context, $kind);

            if ($cycle->status === BillingCycleStatus::Finalized || $cycle->status === BillingCycleStatus::Cancelled) {
                throw ValidationException::withMessages(['cycle' => ['Cannot attach running charts to a finalized or cancelled cycle.']]);
            }

            $agreement = $cycle->customerAgreement ?? $cycle->ownerAgreement;
            $terms = $agreement?->terms ?? [];
            $agreementId = $cycle->customer_agreement_id ?? $cycle->owner_agreement_id;
            $foreignKey = $cycle->customer_agreement_id !== null ? 'customer_agreement_id' : 'owner_agreement_id';

            // Existing chart attachments
            $cycle->charts()->delete();

            $charts = RunningChart::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->whereIn('id', $chartIds)
                ->with(['vehicleUse.vehicle'])
                ->get();

            if (count($chartIds) !== $charts->count()) {
                throw ValidationException::withMessages(['chart_ids' => ['One or more running charts could not be found.']]);
            }

            // Verify agreement ownership and finalized status
            foreach ($charts as $chart) {
                if ((int) ($chart->vehicleUse?->$foreignKey ?? 0) !== (int) $agreementId) {
                    throw ValidationException::withMessages(['chart_ids' => ['One or more running charts do not belong to this agreement.']]);
                }
                if ($chart->status !== RunningChartStatus::Finalized) {
                    throw ValidationException::withMessages(['chart_ids' => ["Running chart {$chart->reference} must be finalized before attaching to a billing cycle."]]);
                }
            }

            // Assert none are already finalized in another billing cycle of the same commercial kind
            $alreadyFinalized = BillingCycleChart::query()
                ->where('tenant_id', $context->tenantId)
                ->where('billing_cycle_id', '!=', $cycle->id)
                ->whereIn('running_chart_id', $chartIds)
                ->whereHas('billingCycle', function ($q) use ($cycle) {
                    $q->where('status', BillingCycleStatus::Finalized);
                    if ($cycle->customer_agreement_id !== null) {
                        $q->whereNotNull('customer_agreement_id');
                    } else {
                        $q->whereNotNull('owner_agreement_id');
                    }
                })
                ->exists();

            if ($alreadyFinalized) {
                throw ValidationException::withMessages(['chart_ids' => ['One or more running charts are already finalized in another billing cycle.']]);
            }

            // Defect 4: Toll expenses aggregation
            $chartIds = $charts->pluck('id')->all();
            $useIds = $charts->pluck('vehicle_use_id')->filter()->unique()->all();
            $tollExpenses = (empty($chartIds) && empty($useIds)) ? collect() : RentalExpense::query()
                ->forTenant($context->tenantId)
                ->where(function (Builder $q) use ($chartIds, $useIds) {
                    if (! empty($chartIds)) {
                        $q->whereIn('running_chart_id', $chartIds);
                    }
                    if (! empty($useIds)) {
                        $q->orWhere(function (Builder $sub) use ($useIds) {
                            $sub->whereIn('vehicle_use_id', $useIds)->whereNull('running_chart_id');
                        });
                    }
                })
                ->where('expense_type', RentalExpenseType::Toll)
                ->whereNotIn('status', [RentalExpenseStatus::Rejected, RentalExpenseStatus::Reversed])
                ->get();

            $tollExpensesByChart = $tollExpenses->whereNotNull('running_chart_id')->groupBy('running_chart_id');
            $tollExpensesByUse = $tollExpenses->whereNull('running_chart_id')->groupBy('vehicle_use_id');

            $totalCommercialKm = '0.000000';
            $totalNormalOtMinutes = 0;
            $totalDoubleOtMinutes = 0;
            $totalTripleOtMinutes = 0;
            $totalNightOuts = 0;
            $totalTolls = '0.000000';

            foreach ($charts as $chart) {
                $commercialKm = (string) ($chart->commercial_km ?? '0.000000');
                $normalOt = (int) ($chart->normal_ot_minutes ?? 0);
                $doubleOt = (int) ($chart->double_ot_minutes ?? 0);
                $tripleOt = (int) ($chart->triple_ot_minutes ?? 0);
                $nightOuts = (int) ($chart->night_outs ?? 0);
                $vehicleId = (int) ($chart->vehicleUse?->vehicle_id ?? 0);

                $chartTolls = '0.000000';
                if (isset($tollExpensesByChart[$chart->id])) {
                    foreach ($tollExpensesByChart[$chart->id] as $tollExp) {
                        $amt = (string) ($tollExp->gross_amount ?? $tollExp->net_amount ?? '0.000000');
                        $chartTolls = $this->math->add($chartTolls, $amt, 6);
                    }
                }
                if ($chart->vehicle_use_id && isset($tollExpensesByUse[$chart->vehicle_use_id])) {
                    foreach ($tollExpensesByUse[$chart->vehicle_use_id] as $tollExp) {
                        $amt = (string) ($tollExp->gross_amount ?? $tollExp->net_amount ?? '0.000000');
                        $chartTolls = $this->math->add($chartTolls, $amt, 6);
                    }
                    unset($tollExpensesByUse[$chart->vehicle_use_id]);
                }

                $chartAttachment = new BillingCycleChart;
                $chartAttachment->tenant_id = $context->tenantId;
                $chartAttachment->billing_cycle_id = $cycle->id;
                $chartAttachment->running_chart_id = $chart->id;
                $chartAttachment->vehicle_id = $vehicleId;
                $chartAttachment->driver_name_snapshot = $chart->driver_name_snapshot ?? $chart->driver_observation ?? 'Assigned Driver';
                $chartAttachment->starts_at = $chart->starts_at;
                $chartAttachment->ends_at = $chart->ends_at;
                $chartAttachment->commercial_km = $commercialKm;
                $chartAttachment->normal_ot_minutes = $normalOt;
                $chartAttachment->double_ot_minutes = $doubleOt;
                $chartAttachment->triple_ot_minutes = $tripleOt;
                $chartAttachment->night_outs = $nightOuts;
                $chartAttachment->tolls_amount = $chartTolls;
                $chartAttachment->is_replacement = (bool) ($chart->vehicleUse?->replaces_use_id !== null);
                $chartAttachment->save();

                $totalCommercialKm = $this->math->add($totalCommercialKm, $commercialKm, 6);
                $totalNormalOtMinutes += $normalOt;
                $totalDoubleOtMinutes += $doubleOt;
                $totalTripleOtMinutes += $tripleOt;
                $totalNightOuts += $nightOuts;
                $totalTolls = $this->math->add($totalTolls, $chartTolls, 6);
            }

            // Rates from agreement terms (unconfigured terms resolve to no automatic charge: '0.000000')
            $freeKmAllowance = (string) ($terms['included_km'] ?? $terms['free_km_allowance'] ?? '0.000000');
            $excessKmRate = (string) ($terms['excess_km_rate'] ?? '0.000000');
            $normalOtRatePerHour = (string) ($terms['normal_ot_rate'] ?? '0.000000');
            $doubleOtRatePerHour = (string) ($terms['double_ot_rate'] ?? '0.000000');
            $tripleOtRatePerHour = (string) ($terms['triple_ot_rate'] ?? '0.000000');
            $nightOutRate = (string) ($terms['night_out_rate'] ?? '0.000000');
            $driverRate = (string) ($terms['driver_rate'] ?? '0.000000');
            $nonAcRate = (string) ($terms['non_ac_rate'] ?? '0.000000');
            $frontAcRate = (string) ($terms['front_ac_rate'] ?? '0.000000');
            $dualAcRate = (string) ($terms['dual_ac_rate'] ?? '0.000000');

            // Defect 10: Clean up any previously generated automatic AC differential and agreed driver items on re-attachment
            $cycle->items()
                ->where('category', BillingCycleItemCategory::Other)
                ->where('description', 'like', 'Air Conditioning Differential%')
                ->delete();

            $cycle->items()
                ->where('category', BillingCycleItemCategory::DriverSalary)
                ->where('description', 'Agreed Driver Rate')
                ->delete();

            // Defect 10: AC differentials
            $baselineKmRate = $this->math->compare($nonAcRate, '0.000000') > 0 ? $nonAcRate : $excessKmRate;
            foreach ($charts as $chart) {
                $acMode = $chart->ac_mode instanceof AirConditioningMode ? $chart->ac_mode->value : (string) $chart->ac_mode;
                $diffRate = '0.000000';
                if ($acMode === AirConditioningMode::Front->value) {
                    if ($this->math->compare($frontAcRate, $baselineKmRate) > 0) {
                        $diffRate = $this->math->sub($frontAcRate, $baselineKmRate, 6);
                    }
                } elseif ($acMode === AirConditioningMode::Dual->value) {
                    if ($this->math->compare($dualAcRate, $baselineKmRate) > 0) {
                        $diffRate = $this->math->sub($dualAcRate, $baselineKmRate, 6);
                    }
                }

                if ($this->math->compare($diffRate, '0.000000') > 0) {
                    $chartKm = (string) ($chart->commercial_km ?? '0.000000');
                    $chartDiffAmount = $this->math->mul($chartKm, $diffRate, 6);
                    if ($this->math->compare($chartDiffAmount, '0.000000') > 0) {
                        $acItem = new BillingCycleItem;
                        $acItem->tenant_id = $context->tenantId;
                        $acItem->billing_cycle_id = $cycle->id;
                        $acItem->item_type = BillingCycleItemType::Addition;
                        $acItem->category = BillingCycleItemCategory::Other;
                        $acItem->description = "Air Conditioning Differential ({$acMode}) · {$chartKm} km @ {$diffRate}/km";
                        $acItem->amount = $chartDiffAmount;
                        $acItem->vehicle_id = (int) ($chart->vehicleUse?->vehicle_id ?? 0) ?: null;
                        $acItem->driver_employee_id = $chart->driver_employee_id;
                        $acItem->save();
                    }
                }
            }

            // Defect 10: Driver Rate
            if ($this->math->compare($driverRate, '0.000000') > 0) {
                $drvItem = new BillingCycleItem;
                $drvItem->tenant_id = $context->tenantId;
                $drvItem->billing_cycle_id = $cycle->id;
                $drvItem->item_type = BillingCycleItemType::Addition;
                $drvItem->category = BillingCycleItemCategory::DriverSalary;
                $drvItem->description = 'Agreed Driver Rate';
                $drvItem->amount = $driverRate;
                $driverIds = $charts->pluck('driver_employee_id')->filter()->unique();
                if ($driverIds->count() === 1) {
                    $drvItem->driver_employee_id = $driverIds->first();
                }
                $drvItem->save();
            }

            // Excess KM calculation
            $excessKm = '0.000000';
            if ($this->math->compare($totalCommercialKm, $freeKmAllowance) > 0) {
                $excessKm = $this->math->sub($totalCommercialKm, $freeKmAllowance, 6);
            }
            $excessKmAmount = $this->math->mul($excessKm, $excessKmRate, 6);

            // OT calculation
            $normalOtHours = $this->math->div((string) $totalNormalOtMinutes, '60', 6);
            $normalOtAmount = $this->math->mul($normalOtHours, $normalOtRatePerHour, 6);

            $doubleOtHours = $this->math->div((string) $totalDoubleOtMinutes, '60', 6);
            $doubleOtAmount = $this->math->mul($doubleOtHours, $doubleOtRatePerHour, 6);

            $tripleOtHours = $this->math->div((string) $totalTripleOtMinutes, '60', 6);
            $tripleOtAmount = $this->math->mul($tripleOtHours, $tripleOtRatePerHour, 6);

            $overtimeAmount = $this->math->add($normalOtAmount, $doubleOtAmount, 6);
            $overtimeAmount = $this->math->add($overtimeAmount, $tripleOtAmount, 6);
            $nightOutAmount = $this->math->mul((string) $totalNightOuts, $nightOutRate, 6);

            $cycle->excess_km_amount = $excessKmAmount;
            $cycle->overtime_amount = $overtimeAmount;
            $cycle->night_out_amount = $nightOutAmount;
            $cycle->tolls_amount = $totalTolls;

            $this->recalculateCycleTotals($cycle);

            $cycle->status = BillingCycleStatus::Stage2Attached;
            $cycle->billing_mode = BillingCycleMode::WithRunningChart;
            $cycle->row_version += 1;
            $cycle->save();

            return $cycle->load(['charts.runningChart', 'charts.vehicle', 'items']);
        });
    }

    public function addItem(
        AgreementContext $context,
        int $cycleId,
        array $data,
        int $expectedVersion
    ): BillingCycleItem {
        return DB::transaction(function () use ($context, $cycleId, $data, $expectedVersion): BillingCycleItem {
            $cycle = BillingCycle::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->lockForUpdate()
                ->findOrFail($cycleId);

            $this->assertCycleVersion($cycle, $expectedVersion);

            $kind = $cycle->customer_agreement_id !== null ? AgreementKind::Customer : AgreementKind::Owner;
            $this->authorization->assertBilling($context, $kind);

            if ($cycle->status === BillingCycleStatus::Finalized || $cycle->status === BillingCycleStatus::Cancelled) {
                throw ValidationException::withMessages(['cycle' => ['Cannot add items to a finalized or cancelled cycle.']]);
            }

            $itemType = BillingCycleItemType::from((string) $data['item_type']);
            $category = BillingCycleItemCategory::from((string) $data['category']);
            $quantity = (string) ($data['quantity'] ?? '1.000000');
            $unitRate = (string) ($data['unit_rate'] ?? '0.000000');
            $amount = $this->math->mul($quantity, $unitRate, 6);
            $vehicleId = ! empty($data['vehicle_id']) ? (int) $data['vehicle_id'] : null;
            $driverEmployeeId = ! empty($data['driver_employee_id']) ? (int) $data['driver_employee_id'] : null;

            $item = new BillingCycleItem;
            $item->tenant_id = $context->tenantId;
            $item->billing_cycle_id = $cycle->id;
            $item->item_type = $itemType;
            $item->category = $category;
            $item->description = (string) ($data['description'] ?? $category->value);
            $item->quantity = $quantity;
            $item->unit_rate = $unitRate;
            $item->amount = $amount;
            $item->vehicle_id = $vehicleId;
            $item->driver_employee_id = $driverEmployeeId;
            $item->save();

            $this->recalculateCycleTotals($cycle);
            $cycle->row_version += 1;
            $cycle->save();

            return $item->load(['vehicle', 'driver']);
        });
    }

    public function removeItem(
        AgreementContext $context,
        int $cycleId,
        int $itemId,
        int $expectedVersion
    ): void {
        DB::transaction(function () use ($context, $cycleId, $itemId, $expectedVersion): void {
            $cycle = BillingCycle::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->lockForUpdate()
                ->findOrFail($cycleId);

            $this->assertCycleVersion($cycle, $expectedVersion);

            $kind = $cycle->customer_agreement_id !== null ? AgreementKind::Customer : AgreementKind::Owner;
            $this->authorization->assertBilling($context, $kind);

            if ($cycle->status === BillingCycleStatus::Finalized || $cycle->status === BillingCycleStatus::Cancelled) {
                throw ValidationException::withMessages(['cycle' => ['Cannot remove items from a finalized or cancelled cycle.']]);
            }

            $item = BillingCycleItem::query()
                ->where('tenant_id', $context->tenantId)
                ->where('billing_cycle_id', $cycle->id)
                ->findOrFail($itemId);

            $item->delete();

            $this->recalculateCycleTotals($cycle);
            $cycle->row_version += 1;
            $cycle->save();
        });
    }

    public function previewFinalStage(AgreementContext $context, int $cycleId): array
    {
        $cycle = BillingCycle::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->with(['charts.runningChart', 'charts.vehicle', 'items.vehicle', 'items.driver', 'initialInvoice.balance'])
            ->findOrFail($cycleId);

        if ($cycle->status === BillingCycleStatus::Cancelled) {
            throw ValidationException::withMessages(['cycle' => ['Cannot preview final stage of a cancelled billing cycle.']]);
        }

        $kind = $cycle->customer_agreement_id !== null ? AgreementKind::Customer : AgreementKind::Owner;
        $this->authorization->assertBilling($context, $kind);

        $agreement = $cycle->customerAgreement ?? $cycle->ownerAgreement;
        $terms = $agreement?->terms ?? [];
        $grossDelta = $this->math->sub((string) $cycle->net_subtotal, (string) $cycle->base_rent_amount, 6);
        $cycleDays = max(1, $cycle->period_from->diffInDays($cycle->period_until) + 1);

        $normalOtRate = (string) ($terms['normal_ot_rate'] ?? '0.000000');
        $doubleOtRate = (string) ($terms['double_ot_rate'] ?? '0.000000');
        $tripleOtRate = (string) ($terms['triple_ot_rate'] ?? '0.000000');
        $nightOutRate = (string) ($terms['night_out_rate'] ?? '0.000000');

        // Physical custody calculation derived from VehicleUse handover/return boundaries
        $foreignKey = $cycle->customer_agreement_id !== null ? 'customer_agreement_id' : 'owner_agreement_id';
        $agreementId = $cycle->customer_agreement_id ?? $cycle->owner_agreement_id;

        $uses = VehicleUse::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->where($foreignKey, $agreementId)
            ->where('starts_at', '<=', $cycle->period_until->endOfDay())
            ->where(function ($q) use ($cycle) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $cycle->period_from->startOfDay())
                    ->orWhere('returned_at', '>=', $cycle->period_from->startOfDay());
            })
            ->where('status', '!=', VehicleUseStatus::Cancelled->value)
            ->with('vehicle')
            ->orderBy('starts_at')
            ->get();

        $totalCustodyDays = 0;
        $custodyMap = [];
        $useCount = $uses->count();
        foreach ($uses as $index => $use) {
            $handoverTime = $use->handed_over_at ?? $use->starts_at;
            $returnTime = $use->returned_at ?? $use->ends_at;

            $startStr = $handoverTime?->toDateString() ?? $cycle->period_from->toDateString();
            $endStr = $returnTime?->toDateString() ?? $cycle->period_until->toDateString();

            $uStartDay = CarbonImmutable::parse(max($startStr, $cycle->period_from->toDateString()))->startOfDay();
            $uEndDay = CarbonImmutable::parse(min($endStr, $cycle->period_until->toDateString()))->startOfDay();

            if ($uStartDay->greaterThan($uEndDay)) {
                $days = 0;
            } else {
                $hasNextConsecutive = false;
                if ($index < $useCount - 1) {
                    $nextUse = $uses[$index + 1];
                    $nextHandover = ($nextUse->handed_over_at ?? $nextUse->starts_at)?->toDateString() ?? $cycle->period_from->toDateString();
                    $nextStartDay = CarbonImmutable::parse(max($nextHandover, $cycle->period_from->toDateString()))->startOfDay();
                    if (($nextStartDay->equalTo($uEndDay) && $nextStartDay->greaterThan($uStartDay))
                        || ($nextUse->replaces_use_id === $use->id && $nextStartDay->lessThanOrEqualTo($uEndDay) && $nextStartDay->greaterThan($uStartDay))) {
                        $hasNextConsecutive = true;
                    }
                }

                if ($hasNextConsecutive) {
                    $days = (int) min($cycleDays, $uStartDay->diffInDays($uEndDay));
                } else {
                    $days = (int) min($cycleDays, $uStartDay->diffInDays($uEndDay) + 1);
                }
            }

            if ($days > 0) {
                $custodyMap[$use->vehicle_id] = ($custodyMap[$use->vehicle_id] ?? 0) + $days;
                $totalCustodyDays += $days;
            }
        }

        // 1. Vehicle Attributions (handles both with and without running charts)
        $vehicleAttributions = [];
        $chartsByVehicle = $cycle->charts->groupBy('vehicle_id');
        $totalCycleCommercialKm = '0.000000';
        foreach ($cycle->charts as $c) {
            $totalCycleCommercialKm = $this->math->add($totalCycleCommercialKm, (string) ($c->commercial_km ?? '0.000000'), 6);
        }

        if ($chartsByVehicle->isNotEmpty()) {
            foreach ($chartsByVehicle as $vehicleId => $vCharts) {
                $vehicle = $vCharts->first()->vehicle ?? Vehicle::query()->find($vehicleId);
                $regNo = $vehicle?->registration_number ?? $vehicle?->vehicle_number ?? "Vehicle #{$vehicleId}";
                $label = $vehicle ? trim("{$regNo} · {$vehicle->make} {$vehicle->model}") : $regNo;
                $isReplacement = (bool) $vCharts->first()->is_replacement;

                $vKm = '0.000000';
                $vNormalOt = 0;
                $vDoubleOt = 0;
                $vTripleOt = 0;
                $vNightOuts = 0;
                foreach ($vCharts as $vc) {
                    $vKm = $this->math->add($vKm, (string) $vc->commercial_km, 6);
                    $vNormalOt += (int) $vc->normal_ot_minutes;
                    $vDoubleOt += (int) $vc->double_ot_minutes;
                    $vTripleOt += (int) ($vc->triple_ot_minutes ?? 0);
                    $vNightOuts += (int) $vc->night_outs;
                }

                // Custody days derived from VehicleUse handover/return boundaries
                $custodyDays = $custodyMap[$vehicleId] ?? $cycleDays;

                $vBaseRent = ($chartsByVehicle->count() > 1 && $totalCustodyDays > 0)
                    ? $this->math->div($this->math->mul((string) $cycle->base_rent_amount, (string) $custodyDays, 6), (string) $totalCustodyDays, 6)
                    : (string) $cycle->base_rent_amount;

                // Excess KM and Tolls attribution
                $vExcessKm = '0.000000';
                $vTolls = '0.000000';
                if ($this->math->compare($totalCycleCommercialKm, '0.000000') > 0) {
                    if ($this->math->compare((string) $cycle->excess_km_amount, '0.000000') > 0) {
                        $vExcessKm = $this->math->div($this->math->mul((string) $cycle->excess_km_amount, $vKm, 6), $totalCycleCommercialKm, 6);
                    }
                    if ($this->math->compare((string) $cycle->tolls_amount, '0.000000') > 0) {
                        $vTolls = $this->math->div($this->math->mul((string) $cycle->tolls_amount, $vKm, 6), $totalCycleCommercialKm, 6);
                    }
                } elseif ($chartsByVehicle->count() === 1) {
                    $vExcessKm = (string) $cycle->excess_km_amount;
                    $vTolls = (string) $cycle->tolls_amount;
                } elseif ($chartsByVehicle->count() > 1 && $totalCustodyDays > 0) {
                    $vExcessKm = $this->math->div($this->math->mul((string) $cycle->excess_km_amount, (string) $custodyDays, 6), (string) $totalCustodyDays, 6);
                    $vTolls = $this->math->div($this->math->mul((string) $cycle->tolls_amount, (string) $custodyDays, 6), (string) $totalCustodyDays, 6);
                }

                $vNormalOtHours = $this->math->div((string) $vNormalOt, '60', 6);
                $vDoubleOtHours = $this->math->div((string) $vDoubleOt, '60', 6);
                $vTripleOtHours = $this->math->div((string) $vTripleOt, '60', 6);

                $vNormalOtAmt = $this->math->mul($vNormalOtHours, $normalOtRate, 6);
                $vDoubleOtAmt = $this->math->mul($vDoubleOtHours, $doubleOtRate, 6);
                $vTripleOtAmt = $this->math->mul($vTripleOtHours, $tripleOtRate, 6);
                $vOtAmt = $this->math->add($vNormalOtAmt, $vDoubleOtAmt, 6);
                $vOtAmt = $this->math->add($vOtAmt, $vTripleOtAmt, 6);
                $vNightOutAmt = $this->math->mul((string) $vNightOuts, $nightOutRate, 6);

                // Attributed ad-hoc additions & deductions for this vehicle
                $vAdditions = '0.000000';
                $vDeductions = '0.000000';
                foreach ($cycle->items as $item) {
                    $matches = ((int) $item->vehicle_id === (int) $vehicleId)
                        || ($item->vehicle_id === null && $item->driver_employee_id === null && $chartsByVehicle->count() === 1);
                    if ($matches) {
                        if ($item->item_type === BillingCycleItemType::Addition) {
                            $vAdditions = $this->math->add($vAdditions, (string) $item->amount, 6);
                        } else {
                            $vDeductions = $this->math->add($vDeductions, (string) $item->amount, 6);
                        }
                    }
                }

                $vSubtotal = $this->math->add($vBaseRent, $vExcessKm, 6);
                $vSubtotal = $this->math->add($vSubtotal, $vOtAmt, 6);
                $vSubtotal = $this->math->add($vSubtotal, $vNightOutAmt, 6);
                $vSubtotal = $this->math->add($vSubtotal, $vTolls, 6);
                $vSubtotal = $this->math->add($vSubtotal, $vAdditions, 6);
                $vSubtotal = $this->math->compare($vSubtotal, $vDeductions) > 0
                    ? $this->math->sub($vSubtotal, $vDeductions, 6)
                    : '0.000000';

                $vehicleAttributions[] = [
                    'vehicle_id' => (int) $vehicleId,
                    'vehicle_number' => $regNo,
                    'vehicle_label' => $label,
                    'is_replacement' => $isReplacement,
                    'charts_count' => $vCharts->count(),
                    'custody_days' => $custodyDays,
                    'total_km' => $vKm,
                    'normal_ot_minutes' => $vNormalOt,
                    'double_ot_minutes' => $vDoubleOt,
                    'triple_ot_minutes' => $vTripleOt,
                    'normal_ot_hours' => $vNormalOtHours,
                    'double_ot_hours' => $vDoubleOtHours,
                    'triple_ot_hours' => $vTripleOtHours,
                    'night_outs' => $vNightOuts,
                    'allocated_base_rent' => $vBaseRent,
                    'allocated_excess_km_amount' => $vExcessKm,
                    'allocated_tolls_amount' => $vTolls,
                    'allocated_ot_amount' => $vOtAmt,
                    'allocated_night_out_amount' => $vNightOutAmt,
                    'attributed_additions' => $vAdditions,
                    'attributed_deductions' => $vDeductions,
                    'subtotal' => $vSubtotal,
                ];
            }
        } else {
            // Non-Running Chart mode: derive vehicle attributions from VehicleUse custody periods grouped by vehicle
            $usesByVehicle = $uses->groupBy('vehicle_id');
            foreach ($usesByVehicle as $vehicleId => $vUses) {
                $firstUse = $vUses->first();
                $vehicle = $firstUse->vehicle ?? Vehicle::query()->find($vehicleId);
                $vehicleId = (int) $vehicleId;
                $regNo = $vehicle?->registration_number ?? $vehicle?->vehicle_number ?? "Vehicle #{$vehicleId}";
                $label = $vehicle ? trim("{$regNo} · {$vehicle->make} {$vehicle->model}") : $regNo;
                $days = $custodyMap[$vehicleId] ?? 0;
                if ($days === 0 && $usesByVehicle->count() > 1 && $totalCustodyDays > 0) {
                    continue;
                }
                if ($days === 0) {
                    $days = $cycleDays;
                }

                $vBaseRent = ($usesByVehicle->count() > 1 && $totalCustodyDays > 0)
                    ? $this->math->div($this->math->mul((string) $cycle->base_rent_amount, (string) $days, 6), (string) $totalCustodyDays, 6)
                    : (string) $cycle->base_rent_amount;

                // Excess KM and Tolls attribution in Non-Running Chart mode
                $vExcessKm = '0.000000';
                $vTolls = '0.000000';
                if ($usesByVehicle->count() > 1 && $totalCustodyDays > 0) {
                    $vExcessKm = $this->math->div($this->math->mul((string) $cycle->excess_km_amount, (string) $days, 6), (string) $totalCustodyDays, 6);
                    $vTolls = $this->math->div($this->math->mul((string) $cycle->tolls_amount, (string) $days, 6), (string) $totalCustodyDays, 6);
                } else {
                    $vExcessKm = (string) $cycle->excess_km_amount;
                    $vTolls = (string) $cycle->tolls_amount;
                }

                $vAdditions = '0.000000';
                $vDeductions = '0.000000';
                foreach ($cycle->items as $item) {
                    $matches = ((int) $item->vehicle_id === $vehicleId)
                        || ($item->vehicle_id === null && $item->driver_employee_id === null && $usesByVehicle->count() === 1);
                    if ($matches) {
                        if ($item->item_type === BillingCycleItemType::Addition) {
                            $vAdditions = $this->math->add($vAdditions, (string) $item->amount, 6);
                        } else {
                            $vDeductions = $this->math->add($vDeductions, (string) $item->amount, 6);
                        }
                    }
                }

                $vSubtotal = $this->math->add($vBaseRent, $vExcessKm, 6);
                $vSubtotal = $this->math->add($vSubtotal, $vTolls, 6);
                $vSubtotal = $this->math->add($vSubtotal, $vAdditions, 6);
                $vSubtotal = $this->math->compare($vSubtotal, $vDeductions) > 0
                    ? $this->math->sub($vSubtotal, $vDeductions, 6)
                    : '0.000000';

                $vehicleAttributions[] = [
                    'vehicle_id' => $vehicleId,
                    'vehicle_number' => $regNo,
                    'vehicle_label' => $label,
                    'is_replacement' => $vUses->contains(fn ($u) => $u->replaces_use_id !== null),
                    'charts_count' => 0,
                    'custody_days' => $days,
                    'total_km' => '0.000000',
                    'normal_ot_minutes' => 0,
                    'double_ot_minutes' => 0,
                    'triple_ot_minutes' => 0,
                    'normal_ot_hours' => '0.000000',
                    'double_ot_hours' => '0.000000',
                    'triple_ot_hours' => '0.000000',
                    'night_outs' => 0,
                    'allocated_base_rent' => $vBaseRent,
                    'allocated_excess_km_amount' => $vExcessKm,
                    'allocated_tolls_amount' => $vTolls,
                    'allocated_ot_amount' => '0.000000',
                    'allocated_night_out_amount' => '0.000000',
                    'attributed_additions' => $vAdditions,
                    'attributed_deductions' => $vDeductions,
                    'subtotal' => $vSubtotal,
                ];
            }
        }

        // 2. Driver Attributions
        $driverAttributions = [];
        $chartsByDriver = $cycle->charts->groupBy('driver_name_snapshot');
        $handledDriverIds = [];

        foreach ($chartsByDriver as $driverName => $dCharts) {
            $dKm = '0.000000';
            $dNormalOt = 0;
            $dDoubleOt = 0;
            $dTripleOt = 0;
            $dNightOuts = 0;
            $driverRef = '';
            $driverEmployeeId = null;

            foreach ($dCharts as $dc) {
                $dKm = $this->math->add($dKm, (string) $dc->commercial_km, 6);
                $dNormalOt += (int) $dc->normal_ot_minutes;
                $dDoubleOt += (int) $dc->double_ot_minutes;
                $dTripleOt += (int) ($dc->triple_ot_minutes ?? 0);
                $dNightOuts += (int) $dc->night_outs;
                if ($dc->runningChart?->driver_reference_snapshot) {
                    $driverRef = $dc->runningChart->driver_reference_snapshot;
                }
                if ($dc->runningChart?->driver_employee_id) {
                    $driverEmployeeId = $dc->runningChart->driver_employee_id;
                    $handledDriverIds[$driverEmployeeId] = true;
                }
            }

            $dNormalOtHours = $this->math->div((string) $dNormalOt, '60', 6);
            $dNormalOtAmt = $this->math->mul($dNormalOtHours, $normalOtRate, 6);

            $dDoubleOtHours = $this->math->div((string) $dDoubleOt, '60', 6);
            $dDoubleOtAmt = $this->math->mul($dDoubleOtHours, $doubleOtRate, 6);

            $dTripleOtHours = $this->math->div((string) $dTripleOt, '60', 6);
            $dTripleOtAmt = $this->math->mul($dTripleOtHours, $tripleOtRate, 6);

            $dOtAmt = $this->math->add($dNormalOtAmt, $dDoubleOtAmt, 6);
            $dOtAmt = $this->math->add($dOtAmt, $dTripleOtAmt, 6);
            $dNightOutAmt = $this->math->mul((string) $dNightOuts, $nightOutRate, 6);

            // Attributed driver ad-hoc items
            $dAdditions = '0.000000';
            $dDeductions = '0.000000';
            foreach ($cycle->items as $item) {
                $matches = false;
                if ($driverEmployeeId !== null && (int) $item->driver_employee_id === (int) $driverEmployeeId) {
                    $matches = true;
                } elseif ($item->driver_employee_id === null && ! empty(trim((string) $driverName)) && str_contains(strtolower($item->description), strtolower(trim((string) $driverName)))) {
                    $matches = true;
                }
                if ($matches) {
                    if ($item->item_type === BillingCycleItemType::Addition) {
                        $dAdditions = $this->math->add($dAdditions, (string) $item->amount, 6);
                    } else {
                        $dDeductions = $this->math->add($dDeductions, (string) $item->amount, 6);
                    }
                }
            }

            $dEarnings = $this->math->add($dOtAmt, $dNightOutAmt, 6);
            $dEarnings = $this->math->add($dEarnings, $dAdditions, 6);
            $dEarnings = $this->math->compare($dEarnings, $dDeductions) > 0
                ? $this->math->sub($dEarnings, $dDeductions, 6)
                : '0.000000';

            $driverAttributions[] = [
                'driver_employee_id' => $driverEmployeeId,
                'driver_name' => (string) $driverName,
                'driver_reference' => $driverRef,
                'charts_count' => $dCharts->count(),
                'total_km' => $dKm,
                'normal_ot_hours' => $dNormalOtHours,
                'normal_ot_amount' => $dNormalOtAmt,
                'double_ot_hours' => $dDoubleOtHours,
                'double_ot_amount' => $dDoubleOtAmt,
                'triple_ot_hours' => $dTripleOtHours,
                'triple_ot_amount' => $dTripleOtAmt,
                'night_outs' => $dNightOuts,
                'night_out_amount' => $dNightOutAmt,
                'attributed_additions' => $dAdditions,
                'attributed_deductions' => $dDeductions,
                'total_earnings' => $dEarnings,
            ];
        }

        // Include drivers who have attributed line items but no running charts
        $itemsWithDriver = $cycle->items->whereNotNull('driver_employee_id');
        foreach ($itemsWithDriver as $item) {
            $drvId = (int) $item->driver_employee_id;
            if (isset($handledDriverIds[$drvId])) {
                continue;
            }
            $handledDriverIds[$drvId] = true;
            $driver = $item->driver ?? HrEmployee::query()->find($drvId);
            $dName = $driver?->display_name ?? "Employee #{$drvId}";
            $dRef = $driver?->employee_number ?? $driver?->code ?? '';

            $dAdditions = '0.000000';
            $dDeductions = '0.000000';
            foreach ($cycle->items->where('driver_employee_id', $drvId) as $dItem) {
                if ($dItem->item_type === BillingCycleItemType::Addition) {
                    $dAdditions = $this->math->add($dAdditions, (string) $dItem->amount, 6);
                } else {
                    $dDeductions = $this->math->add($dDeductions, (string) $dItem->amount, 6);
                }
            }
            $dEarnings = $this->math->compare($dAdditions, $dDeductions) > 0
                ? $this->math->sub($dAdditions, $dDeductions, 6)
                : '0.000000';

            $driverAttributions[] = [
                'driver_employee_id' => $drvId,
                'driver_name' => $dName,
                'driver_reference' => $dRef,
                'charts_count' => 0,
                'total_km' => '0.000000',
                'normal_ot_hours' => '0.000000',
                'normal_ot_amount' => '0.000000',
                'double_ot_hours' => '0.000000',
                'double_ot_amount' => '0.000000',
                'triple_ot_hours' => '0.000000',
                'triple_ot_amount' => '0.000000',
                'night_outs' => 0,
                'night_out_amount' => '0.000000',
                'attributed_additions' => $dAdditions,
                'attributed_deductions' => $dDeductions,
                'total_earnings' => $dEarnings,
            ];
        }

        // 3. Line Items
        $lineItems = $cycle->items->map(function (BillingCycleItem $item) {
            $vehicle = $item->vehicle;
            $driver = $item->driver;

            return [
                'id' => $item->id,
                'item_type' => $item->item_type->value,
                'category' => $item->category->value,
                'description' => $item->description,
                'quantity' => (string) $item->quantity,
                'unit_rate' => (string) $item->unit_rate,
                'amount' => (string) $item->amount,
                'vehicle_id' => $item->vehicle_id,
                'vehicle_number' => $vehicle?->registration_number ?? $vehicle?->vehicle_number,
                'driver_employee_id' => $item->driver_employee_id,
                'driver_name' => $driver?->display_name,
            ];
        })->all();

        // 4. Reassignments in this period
        $reassignments = VehicleRentalReassignment::query()
            ->where('tenant_id', $context->tenantId)
            ->where(function ($q) use ($cycle) {
                if ($cycle->customer_agreement_id !== null) {
                    $q->where('customer_agreement_id', $cycle->customer_agreement_id);
                } else {
                    $q->where('previous_owner_agreement_id', $cycle->owner_agreement_id)
                        ->orWhere('new_owner_agreement_id', $cycle->owner_agreement_id);
                }
            })
            ->where('occurred_at', '>=', $cycle->period_from->startOfDay())
            ->where('occurred_at', '<=', $cycle->period_until->endOfDay())
            ->with(['previousVehicle', 'newVehicle', 'previousDriver', 'newDriver'])
            ->orderBy('occurred_at')
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'type' => $r->reassignment_type->value,
                'occurred_at' => $r->occurred_at?->toIso8601String(),
                'reason' => $r->reason,
                'previous_vehicle' => $r->previousVehicle?->registration_number ?? $r->previousVehicle?->vehicle_number,
                'new_vehicle' => $r->newVehicle?->registration_number ?? $r->newVehicle?->vehicle_number,
                'previous_driver' => $r->previousDriver?->display_name,
                'new_driver' => $r->newDriver?->display_name,
                'financial_status' => $r->financial_status->value,
                'financial_delta' => (string) $r->financial_delta_amount,
            ])
            ->all();

        return [
            'cycle_id' => $cycle->id,
            'cycle_number' => $cycle->cycle_number,
            'status' => $cycle->status->value,
            'period_from' => $cycle->period_from?->format('Y-m-d'),
            'period_until' => $cycle->period_until?->format('Y-m-d'),
            'billing_mode' => $cycle->billing_mode->value,
            'finalization_mode' => $cycle->finalization_mode->value,
            'base_rent_amount' => $cycle->base_rent_amount,
            'excess_km_amount' => $cycle->excess_km_amount,
            'overtime_amount' => $cycle->overtime_amount,
            'night_out_amount' => $cycle->night_out_amount,
            'tolls_amount' => $cycle->tolls_amount,
            'additions_amount' => $cycle->additions_amount,
            'deductions_amount' => $cycle->deductions_amount,
            'net_subtotal' => $cycle->net_subtotal,
            'tax_amount' => $cycle->tax_amount,
            'total_billed_amount' => $cycle->total_billed_amount,
            'initial_invoice' => $cycle->initialInvoice ? [
                'id' => $cycle->initialInvoice->id,
                'invoice_number' => $cycle->initialInvoice->invoice_number,
                'grand_total' => (string) $cycle->initialInvoice->grand_total,
                'paid_total' => (string) $cycle->initialInvoice->paid_total,
                'balance_due' => (string) $cycle->initialInvoice->balance_due,
            ] : null,
            'gross_delta_amount' => $grossDelta,
            'has_running_charts' => $cycle->charts->isNotEmpty(),
            'charts_count' => $cycle->charts->count(),
            'items_count' => $cycle->items->count(),
            'vehicle_attributions' => $vehicleAttributions,
            'driver_attributions' => $driverAttributions,
            'line_items' => $lineItems,
            'reassignments' => $reassignments,
        ];
    }

    public function previewQuotationDocument(AgreementContext $context, int $cycleId): array
    {
        $cycle = BillingCycle::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->with(['charts.runningChart', 'charts.vehicle', 'items.vehicle', 'items.driver', 'initialInvoice.balance', 'customerAgreement.party', 'customerAgreement.vehicleUses.vehicle', 'ownerAgreement.party', 'ownerAgreement.vehicle'])
            ->findOrFail($cycleId);

        $kind = $cycle->customer_agreement_id !== null ? AgreementKind::Customer : AgreementKind::Owner;
        $this->authorization->assertBilling($context, $kind);

        $preview = $this->previewFinalStage($context, $cycleId);
        $agreement = $cycle->customerAgreement ?? $cycle->ownerAgreement;

        $consolidated = $this->buildConsolidatedLines(
            $context,
            $agreement,
            $cycle,
            (string) $cycle->net_subtotal,
            'vehicle_rental_billing_cycle',
            $cycle->id
        );

        $linesArray = [];
        foreach ($consolidated['lines'] as $line) {
            $discount = isset($line->discountAmount) && is_numeric($line->discountAmount) ? (string) $line->discountAmount : '0.000000';
            $lineTotal = $this->math->sub(
                $this->math->mul((string) $line->quantity, (string) $line->unitPrice, 6),
                $discount,
                6
            );
            $linesArray[] = [
                'line_number' => $line->lineNumber,
                'description' => $line->description,
                'quantity' => (string) $line->quantity,
                'unit_price' => (string) $line->unitPrice,
                'discount_amount' => $discount,
                'line_total' => $lineTotal,
            ];
        }

        $party = $agreement?->party;
        $tripsCount = $cycle->charts->count();
        $commercialKm = (string) $cycle->charts->sum('commercial_km');
        $normalOtMinutes = (int) $cycle->charts->sum('normal_ot_minutes');
        $doubleOtMinutes = (int) $cycle->charts->sum('double_ot_minutes');
        $tripleOtMinutes = (int) $cycle->charts->sum('triple_ot_minutes');
        $nightOuts = (int) $cycle->charts->sum('night_outs');

        $normalOtHours = $this->math->div((string) $normalOtMinutes, '60', 6);
        $doubleOtHours = $this->math->div((string) $doubleOtMinutes, '60', 6);
        $tripleOtHours = $this->math->div((string) $tripleOtMinutes, '60', 6);

        $assignedVehicles = $cycle->charts->map(fn ($c) => $c->vehicle?->registration_number ?? $c->vehicle?->vehicle_number)->filter()->unique()->values()->all();
        $assignedDrivers = $cycle->charts->map(fn ($c) => $c->driver_name_snapshot ?? $c->runningChart?->driver_reference_snapshot)->filter()->unique()->values()->all();

        $grossSubtotal = $this->math->add(
            (string) $cycle->base_rent_amount,
            $this->math->add(
                (string) $cycle->excess_km_amount,
                $this->math->add(
                    (string) $cycle->overtime_amount,
                    $this->math->add(
                        (string) $cycle->night_out_amount,
                        $this->math->add((string) $cycle->tolls_amount, (string) $cycle->additions_amount, 6),
                        6
                    ),
                    6
                ),
                6
            ),
            6
        );

        $discountTotal = (string) $cycle->deductions_amount;
        $netSubtotal = (string) $cycle->net_subtotal;
        $taxAmount = (string) $cycle->tax_amount;
        $grandTotal = $this->math->compare((string) $cycle->total_billed_amount, '0.000000') > 0
            ? (string) $cycle->total_billed_amount
            : $this->math->add($netSubtotal, $taxAmount, 6);

        return [
            'quotation_number' => 'QUOT-'.($agreement?->reference ?? 'DRAFT')."-C{$cycle->cycle_number}",
            'quotation_date' => now()->toDateString(),
            'direction' => $kind === AgreementKind::Customer ? 'outbound' : 'inbound',
            'customer' => $party ? [
                'id' => $party->id,
                'name' => $party->display_name ?? $party->name,
                'email' => $party->email ?? null,
                'phone' => $party->phone ?? null,
            ] : null,
            'agreement' => [
                'id' => $agreement?->id,
                'reference' => $agreement?->reference,
                'billing_basis' => $agreement?->billing_basis?->value ?? 'monthly',
                'starts_on' => $agreement?->starts_on?->toDateString(),
                'ends_on' => $agreement?->ends_on?->toDateString(),
            ],
            'billing_cycle' => [
                'id' => $cycle->id,
                'cycle_number' => $cycle->cycle_number,
                'period_from' => $cycle->period_from?->toDateString(),
                'period_until' => $cycle->period_until?->toDateString(),
                'status' => $cycle->status->value,
                'finalization_mode' => $cycle->finalization_mode->value,
            ],
            'period' => [
                'from' => $cycle->period_from?->toDateString(),
                'until' => $cycle->period_until?->toDateString(),
            ],
            'lines' => $linesArray,
            'telematics' => [
                'trips_count' => $tripsCount,
                'commercial_km' => $commercialKm,
                'normal_ot_hours' => $normalOtHours,
                'double_ot_hours' => $doubleOtHours,
                'triple_ot_hours' => $tripleOtHours,
                'night_outs' => $nightOuts,
                'assigned_vehicles' => $assignedVehicles,
                'assigned_drivers' => $assignedDrivers,
            ],
            'vehicle_attributions' => $preview['vehicle_attributions'] ?? [],
            'driver_attributions' => $preview['driver_attributions'] ?? [],
            'line_items' => $preview['line_items'] ?? [],
            'financial_breakdown' => [
                'base_rent' => (string) $cycle->base_rent_amount,
                'excess_km' => (string) $cycle->excess_km_amount,
                'overtime' => (string) $cycle->overtime_amount,
                'night_out' => (string) $cycle->night_out_amount,
                'tolls' => (string) $cycle->tolls_amount,
                'additions' => (string) $cycle->additions_amount,
                'deductions' => (string) $cycle->deductions_amount,
                'gross_subtotal' => $grossSubtotal,
                'discount_total' => $discountTotal,
                'net_subtotal' => $netSubtotal,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
                'net_payable' => $grandTotal,
            ],
        ];
    }

    public function finalize(
        AgreementContext $context,
        int $cycleId,
        array $data,
        int $expectedVersion
    ): BillingCycle {
        return DB::transaction(function () use ($context, $cycleId, $data, $expectedVersion): BillingCycle {
            $cycle = BillingCycle::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->lockForUpdate()
                ->findOrFail($cycleId);

            $this->assertCycleVersion($cycle, $expectedVersion);

            $kind = $cycle->customer_agreement_id !== null ? AgreementKind::Customer : AgreementKind::Owner;
            $this->authorization->assertBilling($context, $kind);

            if ($cycle->status === BillingCycleStatus::Cancelled) {
                throw ValidationException::withMessages(['cycle' => ['Cannot finalize a cancelled billing cycle.']]);
            }

            if ($cycle->status === BillingCycleStatus::Finalized) {
                throw ValidationException::withMessages(['cycle' => ['Billing cycle is already finalized.']]);
            }

            // Assert none of the attached charts have already been finalized in another cycle
            $attachedChartIds = $cycle->charts()->pluck('running_chart_id')->all();
            if (! empty($attachedChartIds)) {
                $alreadyFinalized = BillingCycleChart::query()
                    ->where('tenant_id', $context->tenantId)
                    ->where('billing_cycle_id', '!=', $cycle->id)
                    ->whereIn('running_chart_id', $attachedChartIds)
                    ->whereHas('billingCycle', function ($q) use ($cycle) {
                        $q->where('status', BillingCycleStatus::Finalized);
                        if ($cycle->customer_agreement_id !== null) {
                            $q->whereNotNull('customer_agreement_id');
                        } else {
                            $q->whereNotNull('owner_agreement_id');
                        }
                    })
                    ->exists();

                if ($alreadyFinalized) {
                    throw ValidationException::withMessages(['cycle' => ['One or more running charts attached to this billing cycle have already been finalized in another billing cycle.']]);
                }
            }

            $rawFinalMode = (string) ($data['finalization_mode'] ?? $cycle->finalization_mode->value);
            $finalizationMode = match ($rawFinalMode) {
                'consolidated', 'consolidated_revision' => BillingFinalizationMode::ConsolidatedRevision,
                default => BillingFinalizationMode::SupplementaryInvoice,
            };

            $agreement = $cycle->customerAgreement ?? $cycle->ownerAgreement;

            if ($finalizationMode === BillingFinalizationMode::SupplementaryInvoice) {
                // If initial_invoice_id is null (issue_initial_invoice was false), base rent was never billed.
                // Mode A must bill the full net_subtotal.
                if ($cycle->initial_invoice_id === null) {
                    $amountToBill = (string) $cycle->net_subtotal;
                    if ($this->math->compare($amountToBill, '0.000000') > 0) {
                        $suppInvoice = $this->issueSupplementaryInvoice($kind, $context, $agreement, $cycle, $amountToBill, $data);
                        $cycle->supplementary_invoice_id = $suppInvoice->id;
                        $cycle->tax_amount = (string) $suppInvoice->tax_total;
                        $cycle->total_billed_amount = (string) $suppInvoice->grand_total;
                    } elseif ($this->math->compare($amountToBill, '0.000000') < 0) {
                        $absAmount = substr($amountToBill, 1);
                        $creditNote = $this->issueCreditNoteInvoice($kind, $context, $agreement, $cycle, $absAmount, $data);
                        $cycle->supplementary_invoice_id = $creditNote->id;
                        $cycle->tax_amount = (string) $creditNote->tax_total;
                        $cycle->total_billed_amount = '0.000000';
                    }
                } else {
                    $deltaAmount = $this->math->sub((string) $cycle->net_subtotal, (string) $cycle->base_rent_amount, 6);
                    if ($this->math->compare($deltaAmount, '0.000000') > 0) {
                        $suppInvoice = $this->issueSupplementaryInvoice($kind, $context, $agreement, $cycle, $deltaAmount, $data);
                        $cycle->supplementary_invoice_id = $suppInvoice->id;
                        $totalTax = (string) $suppInvoice->tax_total;
                        $totalBilled = (string) $suppInvoice->grand_total;
                        if ($cycle->initialInvoice) {
                            $totalTax = $this->math->add($totalTax, (string) $cycle->initialInvoice->tax_total, 6);
                            $totalBilled = $this->math->add($totalBilled, (string) $cycle->initialInvoice->grand_total, 6);
                        }
                        $cycle->tax_amount = $totalTax;
                        $cycle->total_billed_amount = $totalBilled;
                    } elseif ($this->math->compare($deltaAmount, '0.000000') < 0) {
                        // Mode A Negative Delta (Downtime Rebates / Discounts):
                        // Issue an authentic Credit Note Invoice for abs($deltaAmount)
                        $absDelta = substr($deltaAmount, 1);
                        $creditNote = $this->issueCreditNoteInvoice($kind, $context, $agreement, $cycle, $absDelta, $data);
                        $cycle->supplementary_invoice_id = $creditNote->id;
                        $initTotal = $cycle->initialInvoice ? (string) $cycle->initialInvoice->grand_total : '0.000000';
                        $netBilled = $this->math->sub($initTotal, (string) $creditNote->grand_total, 6);
                        if ($this->math->compare($netBilled, '0.000000') < 0) {
                            $netBilled = '0.000000';
                        }
                        $cycle->tax_amount = $cycle->initialInvoice ? (string) $cycle->initialInvoice->tax_total : '0.000000';
                        $cycle->total_billed_amount = $netBilled;

                        // Allocate credit note against initial invoice if initial invoice is posted and has open balance
                        if ($cycle->initialInvoice && in_array($cycle->initialInvoice->status, [InvoiceStatus::Posted, InvoiceStatus::PartiallyPaid], true)) {
                            $cycle->initialInvoice->refresh();
                            $initRemaining = (string) ($cycle->initialInvoice->balance?->remaining_amount ?? $cycle->initialInvoice->balance_due ?? '0.000000');
                            if ($this->math->compare($initRemaining, '0.000000') > 0) {
                                $allocAmount = $this->math->compare($absDelta, $initRemaining) > 0 ? $initRemaining : $absDelta;
                                $this->balanceUpdater->allocateCredit($cycle->initialInvoice, 'invoice', $creditNote->id, $allocAmount);
                            }
                        }
                    } else {
                        $cycle->tax_amount = $cycle->initialInvoice ? (string) $cycle->initialInvoice->tax_total : '0.000000';
                        $cycle->total_billed_amount = $cycle->initialInvoice ? (string) $cycle->initialInvoice->grand_total : '0.000000';
                    }
                }
            } else {
                // Mode B: Consolidated revision
                $finalInvoice = $this->issueConsolidatedInvoice($kind, $context, $agreement, $cycle, (string) $cycle->net_subtotal, $data);
                $cycle->final_invoice_id = $finalInvoice->id;
                $cycle->tax_amount = (string) $finalInvoice->tax_total;
                $cycle->total_billed_amount = (string) $finalInvoice->grand_total;

                // Ensure new consolidated invoice is approved and posted even when Stage 1 had no payments
                if ($finalInvoice->status === InvoiceStatus::Draft) {
                    $finalInvoice = $this->invoiceStatusService->transition($finalInvoice, InvoiceStatus::Approved, $context->actorId, 'Consolidated invoice auto-approved');
                }
                if ($finalInvoice->status === InvoiceStatus::Approved) {
                    try {
                        $finalInvoice = $this->invoiceStatusService->transition($finalInvoice, InvoiceStatus::Posted, $context->actorId, 'Consolidated invoice auto-posted');
                    } catch (\Throwable) {
                        // Keep Approved if posting profile is not configured in this scope
                    }
                }

                if ($cycle->initial_invoice_id !== null) {
                    $this->voidInitialInvoiceAndReallocate($context, $agreement, $cycle, $finalInvoice);
                }
            }

            $cycle->finalization_mode = $finalizationMode;
            $cycle->status = BillingCycleStatus::Finalized;
            $cycle->finalized_by = $context->actorId;
            $cycle->finalized_at = now();
            $cycle->row_version += 1;
            $cycle->save();

            return $cycle->load(['customerAgreement', 'ownerAgreement', 'initialInvoice', 'finalInvoice', 'supplementaryInvoice']);
        });
    }

    public function cancelCycle(
        AgreementContext $context,
        int $cycleId,
        int $expectedVersion,
        ?string $reason = null
    ): BillingCycle {
        return DB::transaction(function () use ($context, $cycleId, $expectedVersion, $reason): BillingCycle {
            $cycle = BillingCycle::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->lockForUpdate()
                ->findOrFail($cycleId);

            $this->assertCycleVersion($cycle, $expectedVersion);

            $kind = $cycle->customer_agreement_id !== null ? AgreementKind::Customer : AgreementKind::Owner;
            $this->authorization->assertBilling($context, $kind);

            if ($cycle->status === BillingCycleStatus::Cancelled) {
                return $cycle;
            }

            if ($cycle->status === BillingCycleStatus::Finalized) {
                throw ValidationException::withMessages(['cycle' => ['Finalized billing cycles cannot be cancelled directly. Cancel the generated billing document instead.']]);
            }

            if ($cycle->initial_invoice_id !== null) {
                $initInvoice = Invoice::query()->find($cycle->initial_invoice_id);
                if ($initInvoice && ! in_array($initInvoice->status, [InvoiceStatus::Cancelled, InvoiceStatus::Void, InvoiceStatus::Reversed], true)) {
                    throw ValidationException::withMessages(['cycle' => ["This billing cycle has an active initial invoice ({$initInvoice->invoice_number}). Cancel the invoice instead."]]);
                }
            }

            if ($cycle->supplementary_invoice_id !== null) {
                $suppInvoice = Invoice::query()->find($cycle->supplementary_invoice_id);
                if ($suppInvoice && ! in_array($suppInvoice->status, [InvoiceStatus::Cancelled, InvoiceStatus::Void, InvoiceStatus::Reversed], true)) {
                    throw ValidationException::withMessages(['cycle' => ["This billing cycle has an active supplementary invoice ({$suppInvoice->invoice_number}). Cancel the invoice instead."]]);
                }
            }

            if ($cycle->final_invoice_id !== null) {
                $finalInvoice = Invoice::query()->find($cycle->final_invoice_id);
                if ($finalInvoice && ! in_array($finalInvoice->status, [InvoiceStatus::Cancelled, InvoiceStatus::Void, InvoiceStatus::Reversed], true)) {
                    throw ValidationException::withMessages(['cycle' => ["This billing cycle has an active final invoice ({$finalInvoice->invoice_number}). Cancel the invoice instead."]]);
                }
            }

            $cycle->charts()->delete();
            $cycle->status = BillingCycleStatus::Cancelled;
            if ($reason) {
                $cycle->notes = trim(($cycle->notes ?? '')."\n[Cancelled]: {$reason}");
            }
            $cycle->row_version += 1;
            $cycle->save();

            return $cycle->load(['customerAgreement', 'ownerAgreement']);
        });
    }

    public function getCycleStatementData(AgreementContext $context, int $cycleId): array
    {
        $preview = $this->previewFinalStage($context, $cycleId);
        $cycle = BillingCycle::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->with([
                'charts.runningChart',
                'charts.vehicle',
                'items',
                'initialInvoice',
                'finalInvoice',
                'supplementaryInvoice',
                'customerAgreement.customer',
                'customerAgreement.currency',
                'ownerAgreement.supplier',
                'ownerAgreement.currency',
            ])
            ->findOrFail($cycleId);

        $agreement = $cycle->customerAgreement ?? $cycle->ownerAgreement;
        $party = $cycle->customerAgreement?->customer ?? $cycle->ownerAgreement?->supplier;
        $currencyCode = $cycle->customerAgreement?->currency?->code ?? $cycle->ownerAgreement?->currency?->code ?? 'USD';

        return [
            'header' => [
                'company_name' => 'AutoERP Vehicle Rental Operations',
                'report_title' => 'Vehicle Rental Billing Statement & Invoicing Reconciliation',
                'cycle_number' => $cycle->cycle_number,
                'as_of_date' => now()->toDateString(),
                'status' => strtoupper($cycle->status->value),
                'mode' => $cycle->billing_mode->value,
                'finalization_mode' => $cycle->finalization_mode->value,
            ],
            'agreement' => [
                'id' => $agreement?->id,
                'reference' => $agreement?->reference ?? '—',
                'starts_on' => $agreement?->starts_on?->format('d/m/Y') ?? '—',
                'ends_on' => $agreement?->ends_on?->format('d/m/Y') ?? 'Open-ended',
                'period_from' => $cycle->period_from?->format('d/m/Y'),
                'period_until' => $cycle->period_until?->format('d/m/Y'),
                'currency' => $currencyCode,
            ],
            'party' => [
                'name' => $party?->display_name ?? $party?->name ?? 'Commercial Client',
                'code' => $party?->code ?? $party?->customer_number ?? '—',
                'phone' => $party?->phone ?? '—',
                'address' => $party?->billing_address ?? $party?->address ?? '—',
            ],
            'financials' => [
                'base_rent_amount' => $cycle->base_rent_amount,
                'excess_km_amount' => $cycle->excess_km_amount,
                'overtime_amount' => $cycle->overtime_amount,
                'night_out_amount' => $cycle->night_out_amount,
                'tolls_amount' => $cycle->tolls_amount,
                'additions_amount' => $cycle->additions_amount,
                'deductions_amount' => $cycle->deductions_amount,
                'net_subtotal' => $cycle->net_subtotal,
                'tax_amount' => $cycle->tax_amount,
                'total_billed_amount' => $cycle->total_billed_amount,
                'total_paid_amount' => $cycle->total_paid_amount,
                'balance_due' => $this->math->sub((string) $cycle->total_billed_amount, (string) $cycle->total_paid_amount, 6),
            ],
            'invoices' => [
                'initial' => $cycle->initialInvoice ? [
                    'number' => $cycle->initialInvoice->invoice_number,
                    'date' => $cycle->initialInvoice->invoice_date->format('d/m/Y'),
                    'total' => (string) $cycle->initialInvoice->grand_total,
                    'paid' => (string) $cycle->initialInvoice->paid_total,
                    'status' => $cycle->initialInvoice->status?->value ?? (string) $cycle->initialInvoice->status,
                ] : null,
                'supplementary' => $cycle->supplementaryInvoice ? [
                    'number' => $cycle->supplementaryInvoice->invoice_number,
                    'date' => $cycle->supplementaryInvoice->invoice_date->format('d/m/Y'),
                    'total' => (string) $cycle->supplementaryInvoice->grand_total,
                    'paid' => (string) $cycle->supplementaryInvoice->paid_total,
                    'status' => $cycle->supplementaryInvoice->status?->value ?? (string) $cycle->supplementaryInvoice->status,
                ] : null,
                'final' => $cycle->finalInvoice ? [
                    'number' => $cycle->finalInvoice->invoice_number,
                    'date' => $cycle->finalInvoice->invoice_date->format('d/m/Y'),
                    'total' => (string) $cycle->finalInvoice->grand_total,
                    'paid' => (string) $cycle->finalInvoice->paid_total,
                    'status' => $cycle->finalInvoice->status?->value ?? (string) $cycle->finalInvoice->status,
                ] : null,
            ],
            'vehicle_attributions' => $preview['vehicle_attributions'] ?? [],
            'driver_attributions' => $preview['driver_attributions'] ?? [],
            'charts' => $cycle->charts->map(fn (BillingCycleChart $c) => [
                'reference' => $c->runningChart?->reference ?? "Chart #{$c->running_chart_id}",
                'starts_at' => $c->starts_at?->format('d/m/Y H:i'),
                'ends_at' => $c->ends_at?->format('d/m/Y H:i'),
                'commercial_km' => $c->commercial_km,
                'normal_ot_minutes' => $c->normal_ot_minutes,
                'double_ot_minutes' => $c->double_ot_minutes,
                'triple_ot_minutes' => $c->triple_ot_minutes,
                'night_outs' => $c->night_outs,
                'driver_name' => $c->driver_name_snapshot,
                'vehicle_number' => $c->vehicle?->registration_number ?? $c->vehicle?->vehicle_number ?? "Vehicle #{$c->vehicle_id}",
                'is_replacement' => (bool) $c->is_replacement,
            ])->all(),
            'items' => $preview['line_items'] ?? [],
            'reassignments' => $preview['reassignments'] ?? [],
        ];
    }

    private function recalculateCycleTotals(BillingCycle $cycle): void
    {
        $items = $cycle->items()->get();

        $additions = '0.000000';
        $deductions = '0.000000';
        foreach ($items as $item) {
            if ($item->item_type === BillingCycleItemType::Addition) {
                $additions = $this->math->add($additions, (string) $item->amount, 6);
            } else {
                $deductions = $this->math->add($deductions, (string) $item->amount, 6);
            }
        }

        $gross = $this->math->add((string) $cycle->base_rent_amount, (string) $cycle->excess_km_amount, 6);
        $gross = $this->math->add($gross, (string) $cycle->overtime_amount, 6);
        $gross = $this->math->add($gross, (string) $cycle->night_out_amount, 6);
        $gross = $this->math->add($gross, (string) $cycle->tolls_amount, 6);
        $gross = $this->math->add($gross, $additions, 6);

        $net = $this->math->sub($gross, $deductions, 6);

        $cycle->additions_amount = $additions;
        $cycle->deductions_amount = $deductions;
        $cycle->net_subtotal = $net;
        $cycle->total_billed_amount = $net;
    }

    /** @return array{0: string, 1: ?string} */
    private function resolveInvoiceAndDueDate(array $input): array
    {
        $invoiceDate = (string) ($input['invoice_date'] ?? now()->toDateString());
        $dueDate = ! empty($input['due_date']) ? (string) $input['due_date'] : null;
        if ($dueDate !== null && $dueDate < $invoiceDate) {
            throw ValidationException::withMessages([
                'due_date' => ['The due date cannot be earlier than the invoice date.'],
            ]);
        }

        return [$invoiceDate, $dueDate];
    }

    private function issueStage1Invoice(
        AgreementKind $kind,
        AgreementContext $context,
        $agreement,
        BillingCycle $cycle,
        string $amount,
        string $exchangeRate,
        array $input
    ): Invoice {
        $customer = $kind === AgreementKind::Customer;
        $direction = $customer ? InvoiceDirection::Outbound : InvoiceDirection::Inbound;
        $partyType = $customer ? InvoicePartyType::Customer->value : InvoicePartyType::Supplier->value;
        $partyId = $customer ? $agreement->customer_id : $agreement->supplier_id;
        [$invoiceDate, $dueDate] = $this->resolveInvoiceAndDueDate($input);

        $sourceType = 'vehicle_rental_billing_cycle';
        $sourceId = (int) $cycle->id;

        $itemization = $this->buildStage1Lines(
            $context,
            $agreement,
            $cycle,
            $amount,
            $sourceType,
            $sourceId
        );

        $source = new CreateInvoiceData(
            tenantId: $context->tenantId,
            organizationUnitId: $context->organizationUnitId,
            invoiceType: $customer ? InvoiceType::Sales : InvoiceType::Purchase,
            direction: $direction,
            invoiceDate: $invoiceDate,
            dueDate: $dueDate,
            currencyId: $agreement->currency_id,
            exchangeRate: $exchangeRate,
            partyType: $partyType,
            partyId: $partyId,
            createdBy: $context->actorId,
            notes: 'Stage 1 Provisional Base Rent Invoice · Cycle: '.$cycle->cycle_number,
            supplyPeriodStart: $cycle->period_from?->toDateString(),
            supplyPeriodEnd: $cycle->period_until?->toDateString(),
            lines: $itemization['lines'],
            sources: [
                new InvoiceSourceData(
                    tenantId: $context->tenantId,
                    organizationUnitId: $context->organizationUnitId,
                    sourceType: $sourceType,
                    sourceId: $sourceId,
                    sourceDocumentNumber: $agreement->reference,
                    sourceDocumentDate: $invoiceDate,
                    sourceSubtotal: $amount,
                    sourceGrandTotal: $amount,
                ),
            ],
            sourceLines: $itemization['sourceLines'],
            purchaserReferenceFields: $itemization['referenceFields'],
        );

        return $this->invoiceService->create(
            $this->invoiceFactory->prepare(
                $source,
                $customer ? FinancePostingProfileCode::CustomerRentalInvoice : FinancePostingProfileCode::SupplierRentalInvoice,
                $customer ? FinanceAccountRoleCode::RentalRevenue : FinanceAccountRoleCode::RentalExpense,
            )
        );
    }

    private function issueSupplementaryInvoice(
        AgreementKind $kind,
        AgreementContext $context,
        $agreement,
        BillingCycle $cycle,
        string $deltaAmount,
        array $input
    ): Invoice {
        $customer = $kind === AgreementKind::Customer;
        $direction = $customer ? InvoiceDirection::Outbound : InvoiceDirection::Inbound;
        $partyType = $customer ? InvoicePartyType::Customer->value : InvoicePartyType::Supplier->value;
        $partyId = $customer ? $agreement->customer_id : $agreement->supplier_id;
        [$invoiceDate, $dueDate] = $this->resolveInvoiceAndDueDate($input);

        $sourceType = 'vehicle_rental_billing_cycle_supplementary';
        $sourceId = (int) $cycle->id;

        $itemization = $this->buildSupplementaryLines(
            $context,
            $agreement,
            $cycle,
            $deltaAmount,
            $sourceType,
            $sourceId
        );

        $source = new CreateInvoiceData(
            tenantId: $context->tenantId,
            organizationUnitId: $context->organizationUnitId,
            invoiceType: $customer ? InvoiceType::Sales : InvoiceType::Purchase,
            direction: $direction,
            invoiceDate: $invoiceDate,
            dueDate: $dueDate,
            currencyId: $agreement->currency_id,
            exchangeRate: (string) $cycle->exchange_rate,
            partyType: $partyType,
            partyId: $partyId,
            createdBy: $context->actorId,
            notes: 'Stage 2 Supplementary Usage Invoice · Cycle: '.$cycle->cycle_number,
            supplyPeriodStart: $cycle->period_from?->toDateString(),
            supplyPeriodEnd: $cycle->period_until?->toDateString(),
            lines: $itemization['lines'],
            sources: [
                new InvoiceSourceData(
                    tenantId: $context->tenantId,
                    organizationUnitId: $context->organizationUnitId,
                    sourceType: $sourceType,
                    sourceId: $sourceId,
                    sourceDocumentNumber: $agreement->reference,
                    sourceDocumentDate: $invoiceDate,
                    sourceSubtotal: $deltaAmount,
                    sourceGrandTotal: $deltaAmount,
                ),
            ],
            sourceLines: $itemization['sourceLines'],
            purchaserReferenceFields: $itemization['referenceFields'],
        );

        return $this->invoiceService->create(
            $this->invoiceFactory->prepare(
                $source,
                $customer ? FinancePostingProfileCode::CustomerRentalInvoice : FinancePostingProfileCode::SupplierRentalInvoice,
                $customer ? FinanceAccountRoleCode::RentalRevenue : FinanceAccountRoleCode::RentalExpense,
            )
        );
    }

    private function issueConsolidatedInvoice(
        AgreementKind $kind,
        AgreementContext $context,
        $agreement,
        BillingCycle $cycle,
        string $totalAmount,
        array $input
    ): Invoice {
        $customer = $kind === AgreementKind::Customer;
        $direction = $customer ? InvoiceDirection::Outbound : InvoiceDirection::Inbound;
        $partyType = $customer ? InvoicePartyType::Customer->value : InvoicePartyType::Supplier->value;
        $partyId = $customer ? $agreement->customer_id : $agreement->supplier_id;
        [$invoiceDate, $dueDate] = $this->resolveInvoiceAndDueDate($input);

        $sourceType = 'vehicle_rental_billing_cycle_consolidated';
        $sourceId = (int) $cycle->id;

        $itemization = $this->buildConsolidatedLines(
            $context,
            $agreement,
            $cycle,
            $totalAmount,
            $sourceType,
            $sourceId
        );

        $source = new CreateInvoiceData(
            tenantId: $context->tenantId,
            organizationUnitId: $context->organizationUnitId,
            invoiceType: $customer ? InvoiceType::Sales : InvoiceType::Purchase,
            direction: $direction,
            invoiceDate: $invoiceDate,
            dueDate: $dueDate,
            currencyId: $agreement->currency_id,
            exchangeRate: (string) $cycle->exchange_rate,
            partyType: $partyType,
            partyId: $partyId,
            createdBy: $context->actorId,
            notes: 'Stage 2 Consolidated Revised Rental Invoice · Cycle: '.$cycle->cycle_number,
            supplyPeriodStart: $cycle->period_from?->toDateString(),
            supplyPeriodEnd: $cycle->period_until?->toDateString(),
            lines: $itemization['lines'],
            sources: [
                new InvoiceSourceData(
                    tenantId: $context->tenantId,
                    organizationUnitId: $context->organizationUnitId,
                    sourceType: $sourceType,
                    sourceId: $sourceId,
                    sourceDocumentNumber: $agreement->reference,
                    sourceDocumentDate: $invoiceDate,
                    sourceSubtotal: $totalAmount,
                    sourceGrandTotal: $totalAmount,
                ),
            ],
            sourceLines: $itemization['sourceLines'],
            purchaserReferenceFields: $itemization['referenceFields'],
        );

        return $this->invoiceService->create(
            $this->invoiceFactory->prepare(
                $source,
                $customer ? FinancePostingProfileCode::CustomerRentalInvoice : FinancePostingProfileCode::SupplierRentalInvoice,
                $customer ? FinanceAccountRoleCode::RentalRevenue : FinanceAccountRoleCode::RentalExpense,
            )
        );
    }

    private function voidInitialInvoiceAndReallocate(
        AgreementContext $context,
        $agreement,
        BillingCycle $cycle,
        Invoice $finalInvoice
    ): void {
        $initialInvoice = Invoice::query()
            ->forTenant($context->tenantId)
            ->with('balance')
            ->findOrFail($cycle->initial_invoice_id);

        $initialAllocations = PaymentAllocation::query()
            ->where('invoice_id', $initialInvoice->id)
            ->where('status', AllocationStatus::Active->value)
            ->with('payment')
            ->get();

        $finalInvoice->refresh();
        if ($initialAllocations->isNotEmpty()) {
            if ($finalInvoice->status === InvoiceStatus::Draft) {
                $finalInvoice = $this->invoiceStatusService->transition($finalInvoice, InvoiceStatus::Approved, $context->actorId, 'Auto-approved for payment reallocation');
            }
            if ($finalInvoice->status === InvoiceStatus::Approved) {
                try {
                    $finalInvoice = $this->invoiceStatusService->transition($finalInvoice, InvoiceStatus::Posted, $context->actorId, 'Auto-posted for payment reallocation');
                } catch (\Throwable) {
                }
            }
        }

        $finalInvoice->refresh();
        $finalInvoice->load('balance');
        $remainingToAllocate = (string) ($finalInvoice->balance?->remaining_amount ?? $finalInvoice->balance_due);
        $transferredPaymentTotal = '0.000000';

        foreach ($initialAllocations as $alloc) {
            $payment = Payment::query()->where('tenant_id', $context->tenantId)->lockForUpdate()->find($alloc->payment_id);
            if ($payment) {
                $reversalDate = $finalInvoice->invoice_date?->toDateString() ?? now()->toDateString();
                if ($alloc->allocation_date && $reversalDate < $alloc->allocation_date->toDateString()) {
                    $reversalDate = $alloc->allocation_date->toDateString();
                }

                // 1. Reverse the allocation from initial invoice back to payment
                $payment = $this->allocationReversalService->reverse(
                    $payment,
                    $alloc->id,
                    (int) $payment->row_version,
                    (int) $alloc->row_version,
                    $reversalDate,
                    'Payment transferred from provisional invoice '.$initialInvoice->invoice_number.' to consolidated revision '.$finalInvoice->invoice_number,
                    $context->actorId,
                );

                $reallocationDate = $finalInvoice->invoice_date?->toDateString() ?? now()->toDateString();
                if ($payment->payment_date && $reallocationDate < $payment->payment_date->toDateString()) {
                    $reallocationDate = $payment->payment_date->toDateString();
                }

                // 2. Bound allocation by available balance on final invoice
                if ($this->math->compare($remainingToAllocate, '0.000000') > 0) {
                    $amountToApply = $this->math->compare((string) $alloc->allocated_amount, $remainingToAllocate) > 0
                        ? $remainingToAllocate
                        : (string) $alloc->allocated_amount;

                    $this->allocationService->allocate(
                        $payment,
                        [
                            new PaymentAllocationData(
                                invoiceId: $finalInvoice->id,
                                allocatedAmount: $amountToApply,
                                allocationDate: $reallocationDate,
                                metadata: [
                                    'transferred_from_invoice_id' => $initialInvoice->id,
                                    'cycle_number' => $cycle->cycle_number,
                                ],
                            ),
                        ],
                        (int) $payment->row_version,
                        $context->actorId,
                    );

                    $transferredPaymentTotal = $this->math->add($transferredPaymentTotal, $amountToApply, 6);
                    $remainingToAllocate = $this->math->sub($remainingToAllocate, $amountToApply, 6);
                }
            }
        }

        $cycle->total_paid_amount = $transferredPaymentTotal;

        // 3. Record Credit Note adjustment offsetting initial invoice
        $adjustment = new AgreementAdjustment;
        $adjustment->tenant_id = $context->tenantId;
        $adjustment->organization_unit_id = $context->organizationUnitId;
        if ($cycle->customer_agreement_id !== null) {
            $adjustment->customer_agreement_id = $cycle->customer_agreement_id;
        } else {
            $adjustment->owner_agreement_id = $cycle->owner_agreement_id;
        }
        $adjustment->invoice_id = $initialInvoice->id;
        $adjustment->adjustment_type = AgreementAdjustmentType::CreditNote;
        $adjustment->category = AgreementAdjustmentCategory::CommercialRebate;
        $adjustment->status = AgreementAdjustmentStatus::Applied;
        $adjustment->amount = (string) $initialInvoice->grand_total;
        $adjustment->original_amount = (string) $initialInvoice->grand_total;
        $adjustment->tax_amount = '0.000000';
        $adjustment->reason = 'Credit Note applied to void Stage 1 provisional invoice '.$initialInvoice->invoice_number.' for consolidated revision: '.$cycle->cycle_number;
        $adjustment->actor_id = $context->actorId;
        $adjustment->authorized_by_user_id = $context->actorId;
        $adjustment->authorized_at = now();
        $adjustment->row_version = AgreementFields::INITIAL_VERSION;
        $adjustment->save();

        $history = new AgreementAdjustmentHistory;
        $history->tenant_id = $context->tenantId;
        $history->adjustment_id = $adjustment->id;
        $history->from_status = AgreementAdjustmentStatus::Draft;
        $history->to_status = AgreementAdjustmentStatus::Applied;
        $history->reason = 'Credit Note applied for revision of Stage 1 invoice '.$initialInvoice->invoice_number;
        $history->user_id = $context->actorId;
        $history->save();

        if (in_array($initialInvoice->status, [InvoiceStatus::Draft, InvoiceStatus::Approved], true)) {
            try {
                $this->invoiceStatusService->transition(
                    $initialInvoice,
                    InvoiceStatus::Void,
                    $context->actorId,
                    'Provisional invoice superseded by consolidated revision invoice '.$finalInvoice->invoice_number
                );
            } catch (\Throwable) {
            }
        } else {
            // Stage 1 invoice was approved/posted.
            // Create and post an authentic Credit Note Invoice and call
            // InvoiceBalanceUpdater::allocateCredit() to legally extinguish the balance in AR.
            $initialInvoice->refresh();
            $remaining = (string) ($initialInvoice->balance?->remaining_amount ?? $initialInvoice->balance_due ?? '0.000000');
            if ($this->math->compare($remaining, '0.000000') > 0) {
                $kind = $cycle->customer_agreement_id !== null ? AgreementKind::Customer : AgreementKind::Owner;
                $creditNote = $this->issueCreditNoteInvoice(
                    $kind,
                    $context,
                    $agreement,
                    $cycle,
                    $remaining,
                    [],
                    'Credit Note to extinguish provisional invoice '.$initialInvoice->invoice_number.' superseded by consolidated revision invoice '.$finalInvoice->invoice_number
                );
                $this->balanceUpdater->allocateCredit($initialInvoice, 'invoice', $creditNote->id, $remaining);
            }
        }
    }

    private function issueCreditNoteInvoice(
        AgreementKind $kind,
        AgreementContext $context,
        $agreement,
        BillingCycle $cycle,
        string $amount,
        array $input,
        string $notes = ''
    ): Invoice {
        $customer = $kind === AgreementKind::Customer;
        $direction = $customer ? InvoiceDirection::Outbound : InvoiceDirection::Inbound;
        $partyType = $customer ? InvoicePartyType::Customer->value : InvoicePartyType::Supplier->value;
        $partyId = $customer ? $agreement->customer_id : $agreement->supplier_id;
        [$invoiceDate, $dueDate] = $this->resolveInvoiceAndDueDate($input);

        $sourceType = 'vehicle_rental_billing_cycle_credit_note';
        $sourceId = (int) $cycle->id;

        $itemization = $this->buildCreditNoteLines(
            $context,
            $agreement,
            $cycle,
            $amount,
            $sourceType,
            $sourceId,
            $notes
        );

        $source = new CreateInvoiceData(
            tenantId: $context->tenantId,
            organizationUnitId: $context->organizationUnitId,
            invoiceType: InvoiceType::Credit,
            direction: $direction,
            invoiceDate: $invoiceDate,
            dueDate: $dueDate,
            currencyId: $agreement->currency_id,
            exchangeRate: (string) $cycle->exchange_rate,
            partyType: $partyType,
            partyId: $partyId,
            createdBy: $context->actorId,
            notes: $notes ?: 'Credit Note for Billing Cycle: '.$cycle->cycle_number,
            supplyPeriodStart: $cycle->period_from?->toDateString(),
            supplyPeriodEnd: $cycle->period_until?->toDateString(),
            lines: $itemization['lines'],
            sources: [
                new InvoiceSourceData(
                    tenantId: $context->tenantId,
                    organizationUnitId: $context->organizationUnitId,
                    sourceType: $sourceType,
                    sourceId: $sourceId,
                    sourceDocumentNumber: $agreement->reference,
                    sourceDocumentDate: $invoiceDate,
                    sourceSubtotal: $amount,
                    sourceGrandTotal: $amount,
                ),
            ],
            sourceLines: $itemization['sourceLines'],
            purchaserReferenceFields: $itemization['referenceFields'],
        );

        $creditNote = $this->invoiceService->create(
            $this->invoiceFactory->prepare(
                $source,
                $customer ? FinancePostingProfileCode::CustomerRentalInvoice : FinancePostingProfileCode::SupplierRentalInvoice,
                $customer ? FinanceAccountRoleCode::RentalRevenue : FinanceAccountRoleCode::RentalExpense,
            )
        );

        if ($creditNote->status === InvoiceStatus::Draft) {
            $creditNote = $this->invoiceStatusService->transition($creditNote, InvoiceStatus::Approved, $context->actorId, 'Credit note auto-approved');
        }
        if ($creditNote->status === InvoiceStatus::Approved) {
            try {
                $creditNote = $this->invoiceStatusService->transition($creditNote, InvoiceStatus::Posted, $context->actorId, 'Credit note auto-posted');
            } catch (\Throwable) {
            }
        }

        return $creditNote;
    }

    /**
     * @return array{
     *     lines: list<InvoiceLineData>,
     *     sourceLines: list<InvoiceSourceLineData>,
     *     referenceFields: list<array{label: string, value: string}>
     * }
     */
    private function buildStage1Lines(
        AgreementContext $context,
        $agreement,
        BillingCycle $cycle,
        string $amount,
        string $sourceType,
        int $sourceId
    ): array {
        $cycleDays = max(1, $cycle->period_from->diffInDays($cycle->period_until) + 1);
        $foreignKey = $cycle->customer_agreement_id !== null ? 'customer_agreement_id' : 'owner_agreement_id';
        $agreementId = $cycle->customer_agreement_id ?? $cycle->owner_agreement_id;

        $uses = VehicleUse::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->where($foreignKey, $agreementId)
            ->where('starts_at', '<=', $cycle->period_until->endOfDay())
            ->where(function ($q) use ($cycle) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $cycle->period_from->startOfDay())
                    ->orWhere('returned_at', '>=', $cycle->period_from->startOfDay());
            })
            ->where('status', '!=', VehicleUseStatus::Cancelled->value)
            ->with('vehicle')
            ->orderBy('starts_at')
            ->get();

        $custodyMap = [];
        $totalCustodyDays = 0;
        $useCount = $uses->count();
        foreach ($uses as $index => $use) {
            $handoverTime = $use->handed_over_at ?? $use->starts_at;
            $returnTime = $use->returned_at ?? $use->ends_at;

            $startStr = $handoverTime?->toDateString() ?? $cycle->period_from->toDateString();
            $endStr = $returnTime?->toDateString() ?? $cycle->period_until->toDateString();

            $uStartDay = CarbonImmutable::parse(max($startStr, $cycle->period_from->toDateString()))->startOfDay();
            $uEndDay = CarbonImmutable::parse(min($endStr, $cycle->period_until->toDateString()))->startOfDay();

            if ($uStartDay->greaterThan($uEndDay)) {
                $days = 0;
            } else {
                $hasNextConsecutive = false;
                if ($index < $useCount - 1) {
                    $nextUse = $uses[$index + 1];
                    $nextHandover = ($nextUse->handed_over_at ?? $nextUse->starts_at)?->toDateString() ?? $cycle->period_from->toDateString();
                    $nextStartDay = CarbonImmutable::parse(max($nextHandover, $cycle->period_from->toDateString()))->startOfDay();
                    if (($nextStartDay->equalTo($uEndDay) && $nextStartDay->greaterThan($uStartDay))
                        || ($nextUse->replaces_use_id === $use->id && $nextStartDay->lessThanOrEqualTo($uEndDay) && $nextStartDay->greaterThan($uStartDay))) {
                        $hasNextConsecutive = true;
                    }
                }

                if ($hasNextConsecutive) {
                    $days = (int) min($cycleDays, $uStartDay->diffInDays($uEndDay));
                } else {
                    $days = (int) min($cycleDays, $uStartDay->diffInDays($uEndDay) + 1);
                }
            }

            if ($days > 0) {
                $custodyMap[$use->vehicle_id] = ($custodyMap[$use->vehicle_id] ?? 0) + $days;
                $totalCustodyDays += $days;
            }
        }

        $lines = [];
        $sourceLines = [];
        $vehicleNumbers = [];
        $periodLabel = $cycle->period_from?->format('Y-m-d').' To '.$cycle->period_until?->format('Y-m-d');

        if ($uses->isNotEmpty() && count($custodyMap) > 1 && $totalCustodyDays > 0) {
            $allocatedSoFar = '0.000000';
            $vehicles = $uses->unique('vehicle_id')->values();
            $count = $vehicles->count();

            foreach ($vehicles as $index => $u) {
                $v = $u->vehicle;
                $vId = (int) $u->vehicle_id;
                $vDays = $custodyMap[$vId] ?? 0;
                $regNo = $v?->registration_number ?? $v?->vehicle_number ?? "Vehicle #{$vId}";
                $vehicleNumbers[] = $regNo;

                if ($index === $count - 1) {
                    $vAmount = $this->math->sub($amount, $allocatedSoFar, 6);
                } else {
                    $vAmount = $this->math->div($this->math->mul($amount, (string) $vDays, 6), (string) $totalCustodyDays, 6);
                    $allocatedSoFar = $this->math->add($allocatedSoFar, $vAmount, 6);
                }

                $lineNumber = $index + 1;
                $lineDesc = "Base Rental Fee · {$regNo} · {$vDays} Days ({$periodLabel})";
                $srcLineId = ($sourceId * 1000) + $lineNumber;

                $lines[] = new InvoiceLineData(
                    lineNumber: $lineNumber,
                    description: $lineDesc,
                    quantity: self::WHOLE_CHARGE,
                    unitPrice: $vAmount,
                    lineType: InvoiceLineType::Charge,
                    sourceLineType: $sourceType,
                    sourceLineId: $srcLineId,
                );

                $sourceLines[] = new InvoiceSourceLineData(
                    tenantId: $context->tenantId,
                    organizationUnitId: $context->organizationUnitId,
                    sourceType: $sourceType,
                    sourceId: $sourceId,
                    sourceLineType: $sourceType,
                    sourceLineId: $srcLineId,
                    sourceQuantity: self::WHOLE_CHARGE,
                    invoicedQuantity: self::WHOLE_CHARGE,
                    sourceUnitPrice: $vAmount,
                    sourceLineTotal: $vAmount,
                    invoicedLineTotal: $vAmount,
                );
            }
        } else {
            $regNo = null;
            if ($uses->isNotEmpty()) {
                $v = $uses->first()->vehicle;
                $regNo = $v?->registration_number ?? $v?->vehicle_number;
                if ($regNo) {
                    $vehicleNumbers[] = $regNo;
                }
            }
            $targetLabel = $regNo ?: $agreement->reference;
            $lineDesc = "Base Rental Fee · {$targetLabel} · ({$periodLabel})";
            $srcLineId = ($sourceId * 1000) + 1;

            $lines[] = new InvoiceLineData(
                lineNumber: 1,
                description: $lineDesc,
                quantity: self::WHOLE_CHARGE,
                unitPrice: $amount,
                lineType: InvoiceLineType::Charge,
                sourceLineType: $sourceType,
                sourceLineId: $srcLineId,
            );

            $sourceLines[] = new InvoiceSourceLineData(
                tenantId: $context->tenantId,
                organizationUnitId: $context->organizationUnitId,
                sourceType: $sourceType,
                sourceId: $sourceId,
                sourceLineType: $sourceType,
                sourceLineId: $srcLineId,
                sourceQuantity: self::WHOLE_CHARGE,
                invoicedQuantity: self::WHOLE_CHARGE,
                sourceUnitPrice: $amount,
                sourceLineTotal: $amount,
                invoicedLineTotal: $amount,
            );
        }

        $referenceFields = [
            ['label' => 'Agreement Reference', 'value' => (string) $agreement->reference],
            ['label' => 'Cycle Number', 'value' => (string) $cycle->cycle_number],
            ['label' => 'Billing Period', 'value' => $periodLabel],
        ];
        if (! empty($vehicleNumbers)) {
            $referenceFields[] = ['label' => 'Assigned Vehicle(s)', 'value' => implode(', ', array_unique($vehicleNumbers))];
        }

        return [
            'lines' => $lines,
            'sourceLines' => $sourceLines,
            'referenceFields' => $referenceFields,
        ];
    }

    /**
     * @return array{
     *     lines: list<InvoiceLineData>,
     *     sourceLines: list<InvoiceSourceLineData>,
     *     referenceFields: list<array{label: string, value: string}>
     * }
     */
    private function buildSupplementaryLines(
        AgreementContext $context,
        $agreement,
        BillingCycle $cycle,
        string $deltaAmount,
        string $sourceType,
        int $sourceId
    ): array {
        $cycle->loadMissing(['charts.vehicle', 'items.vehicle', 'items.driver']);
        $terms = $agreement?->terms ?? [];
        $periodLabel = $cycle->period_from?->format('Y-m-d').' To '.$cycle->period_until?->format('Y-m-d');

        $lines = [];
        $sourceLines = [];
        $vehicleNumbers = [];
        $lineNumber = 1;

        // 1. If base rent was not previously billed in Stage 1, bill it now
        if ($cycle->initial_invoice_id === null && $this->math->compare((string) $cycle->base_rent_amount, '0.000000') > 0) {
            $srcLineId = ($sourceId * 1000) + $lineNumber;
            $lines[] = new InvoiceLineData(
                lineNumber: $lineNumber,
                description: "Base Rental Fee · {$agreement->reference} · ({$periodLabel})",
                quantity: self::WHOLE_CHARGE,
                unitPrice: (string) $cycle->base_rent_amount,
                lineType: InvoiceLineType::Charge,
                sourceLineType: $sourceType,
                sourceLineId: $srcLineId,
            );
            $sourceLines[] = new InvoiceSourceLineData(
                tenantId: $context->tenantId,
                organizationUnitId: $context->organizationUnitId,
                sourceType: $sourceType,
                sourceId: $sourceId,
                sourceLineType: $sourceType,
                sourceLineId: $srcLineId,
                sourceQuantity: self::WHOLE_CHARGE,
                invoicedQuantity: self::WHOLE_CHARGE,
                sourceUnitPrice: (string) $cycle->base_rent_amount,
                sourceLineTotal: (string) $cycle->base_rent_amount,
                invoicedLineTotal: (string) $cycle->base_rent_amount,
            );
            $lineNumber++;
        }

        // 2. Excess Kilometers
        if ($this->math->compare((string) $cycle->excess_km_amount, '0.000000') > 0) {
            $excessKmRate = (string) ($terms['excess_km_rate'] ?? '0.000000');
            $freeKm = (string) ($terms['included_km'] ?? $terms['free_km_allowance'] ?? '0.000000');
            $totalKm = '0.000000';
            foreach ($cycle->charts as $c) {
                $totalKm = $this->math->add($totalKm, (string) ($c->commercial_km ?? '0.000000'), 6);
            }
            $excessKm = $this->math->compare($totalKm, $freeKm) > 0 ? $this->math->sub($totalKm, $freeKm, 6) : '0.000000';
            $srcLineId = ($sourceId * 1000) + $lineNumber;

            $lines[] = new InvoiceLineData(
                lineNumber: $lineNumber,
                description: "Excess Mileage Charge · {$excessKm} km @ {$excessKmRate}/km (Included: {$freeKm} km)",
                quantity: self::WHOLE_CHARGE,
                unitPrice: (string) $cycle->excess_km_amount,
                lineType: InvoiceLineType::Charge,
                sourceLineType: $sourceType,
                sourceLineId: $srcLineId,
            );
            $sourceLines[] = new InvoiceSourceLineData(
                tenantId: $context->tenantId,
                organizationUnitId: $context->organizationUnitId,
                sourceType: $sourceType,
                sourceId: $sourceId,
                sourceLineType: $sourceType,
                sourceLineId: $srcLineId,
                sourceQuantity: self::WHOLE_CHARGE,
                invoicedQuantity: self::WHOLE_CHARGE,
                sourceUnitPrice: (string) $cycle->excess_km_amount,
                sourceLineTotal: (string) $cycle->excess_km_amount,
                invoicedLineTotal: (string) $cycle->excess_km_amount,
            );
            $lineNumber++;
        }

        // 3. Overtime Charges (Normal, Double, Triple)
        if ($this->math->compare((string) $cycle->overtime_amount, '0.000000') > 0) {
            $normalOtRate = (string) ($terms['normal_ot_rate'] ?? '0.000000');
            $doubleOtRate = (string) ($terms['double_ot_rate'] ?? '0.000000');
            $tripleOtRate = (string) ($terms['triple_ot_rate'] ?? '0.000000');

            $totNormalMin = (int) $cycle->charts->sum('normal_ot_minutes');
            $totDoubleMin = (int) $cycle->charts->sum('double_ot_minutes');
            $totTripleMin = (int) $cycle->charts->sum('triple_ot_minutes');

            $normalHours = $this->math->div((string) $totNormalMin, '60', 6);
            $doubleHours = $this->math->div((string) $totDoubleMin, '60', 6);
            $tripleHours = $this->math->div((string) $totTripleMin, '60', 6);

            $normalAmt = $this->math->mul($normalHours, $normalOtRate, 6);
            $doubleAmt = $this->math->mul($doubleHours, $doubleOtRate, 6);
            $tripleAmt = $this->math->mul($tripleHours, $tripleOtRate, 6);
            $itemizedOtSum = $this->math->add($this->math->add($normalAmt, $doubleAmt, 6), $tripleAmt, 6);

            if ($this->math->compare($normalAmt, '0.000000') > 0) {
                $srcLineId = ($sourceId * 1000) + $lineNumber;
                $lines[] = new InvoiceLineData(
                    lineNumber: $lineNumber,
                    description: "Driver Overtime · Normal (1.5x) · {$normalHours} hrs @ {$normalOtRate}/hr",
                    quantity: self::WHOLE_CHARGE,
                    unitPrice: $normalAmt,
                    lineType: InvoiceLineType::Charge,
                    sourceLineType: $sourceType,
                    sourceLineId: $srcLineId,
                );
                $sourceLines[] = new InvoiceSourceLineData(
                    tenantId: $context->tenantId,
                    organizationUnitId: $context->organizationUnitId,
                    sourceType: $sourceType,
                    sourceId: $sourceId,
                    sourceLineType: $sourceType,
                    sourceLineId: $srcLineId,
                    sourceQuantity: self::WHOLE_CHARGE,
                    invoicedQuantity: self::WHOLE_CHARGE,
                    sourceUnitPrice: $normalAmt,
                    sourceLineTotal: $normalAmt,
                    invoicedLineTotal: $normalAmt,
                );
                $lineNumber++;
            }

            if ($this->math->compare($doubleAmt, '0.000000') > 0) {
                $srcLineId = ($sourceId * 1000) + $lineNumber;
                $lines[] = new InvoiceLineData(
                    lineNumber: $lineNumber,
                    description: "Driver Overtime · Double (2.0x) · {$doubleHours} hrs @ {$doubleOtRate}/hr",
                    quantity: self::WHOLE_CHARGE,
                    unitPrice: $doubleAmt,
                    lineType: InvoiceLineType::Charge,
                    sourceLineType: $sourceType,
                    sourceLineId: $srcLineId,
                );
                $sourceLines[] = new InvoiceSourceLineData(
                    tenantId: $context->tenantId,
                    organizationUnitId: $context->organizationUnitId,
                    sourceType: $sourceType,
                    sourceId: $sourceId,
                    sourceLineType: $sourceType,
                    sourceLineId: $srcLineId,
                    sourceQuantity: self::WHOLE_CHARGE,
                    invoicedQuantity: self::WHOLE_CHARGE,
                    sourceUnitPrice: $doubleAmt,
                    sourceLineTotal: $doubleAmt,
                    invoicedLineTotal: $doubleAmt,
                );
                $lineNumber++;
            }

            if ($this->math->compare($tripleAmt, '0.000000') > 0) {
                $srcLineId = ($sourceId * 1000) + $lineNumber;
                $lines[] = new InvoiceLineData(
                    lineNumber: $lineNumber,
                    description: "Driver Overtime · Triple (3.0x) · {$tripleHours} hrs @ {$tripleOtRate}/hr",
                    quantity: self::WHOLE_CHARGE,
                    unitPrice: $tripleAmt,
                    lineType: InvoiceLineType::Charge,
                    sourceLineType: $sourceType,
                    sourceLineId: $srcLineId,
                );
                $sourceLines[] = new InvoiceSourceLineData(
                    tenantId: $context->tenantId,
                    organizationUnitId: $context->organizationUnitId,
                    sourceType: $sourceType,
                    sourceId: $sourceId,
                    sourceLineType: $sourceType,
                    sourceLineId: $srcLineId,
                    sourceQuantity: self::WHOLE_CHARGE,
                    invoicedQuantity: self::WHOLE_CHARGE,
                    sourceUnitPrice: $tripleAmt,
                    sourceLineTotal: $tripleAmt,
                    invoicedLineTotal: $tripleAmt,
                );
                $lineNumber++;
            }

            $otRemainder = $this->math->sub((string) $cycle->overtime_amount, $itemizedOtSum, 6);
            if ($this->math->compare($otRemainder, '0.000000') > 0) {
                $srcLineId = ($sourceId * 1000) + $lineNumber;
                $lines[] = new InvoiceLineData(
                    lineNumber: $lineNumber,
                    description: 'Driver Overtime Service Charges',
                    quantity: self::WHOLE_CHARGE,
                    unitPrice: $otRemainder,
                    lineType: InvoiceLineType::Charge,
                    sourceLineType: $sourceType,
                    sourceLineId: $srcLineId,
                );
                $sourceLines[] = new InvoiceSourceLineData(
                    tenantId: $context->tenantId,
                    organizationUnitId: $context->organizationUnitId,
                    sourceType: $sourceType,
                    sourceId: $sourceId,
                    sourceLineType: $sourceType,
                    sourceLineId: $srcLineId,
                    sourceQuantity: self::WHOLE_CHARGE,
                    invoicedQuantity: self::WHOLE_CHARGE,
                    sourceUnitPrice: $otRemainder,
                    sourceLineTotal: $otRemainder,
                    invoicedLineTotal: $otRemainder,
                );
                $lineNumber++;
            }
        }

        // 4. Night-Out Allowance
        if ($this->math->compare((string) $cycle->night_out_amount, '0.000000') > 0) {
            $nightOuts = (int) $cycle->charts->sum('night_outs');
            $nightOutRate = (string) ($terms['night_out_rate'] ?? '0.000000');
            $srcLineId = ($sourceId * 1000) + $lineNumber;

            $lines[] = new InvoiceLineData(
                lineNumber: $lineNumber,
                description: "Driver Night-Out Allowance · {$nightOuts} Nights @ {$nightOutRate}",
                quantity: self::WHOLE_CHARGE,
                unitPrice: (string) $cycle->night_out_amount,
                lineType: InvoiceLineType::Charge,
                sourceLineType: $sourceType,
                sourceLineId: $srcLineId,
            );
            $sourceLines[] = new InvoiceSourceLineData(
                tenantId: $context->tenantId,
                organizationUnitId: $context->organizationUnitId,
                sourceType: $sourceType,
                sourceId: $sourceId,
                sourceLineType: $sourceType,
                sourceLineId: $srcLineId,
                sourceQuantity: self::WHOLE_CHARGE,
                invoicedQuantity: self::WHOLE_CHARGE,
                sourceUnitPrice: (string) $cycle->night_out_amount,
                sourceLineTotal: (string) $cycle->night_out_amount,
                invoicedLineTotal: (string) $cycle->night_out_amount,
            );
            $lineNumber++;
        }

        // 5. Tolls
        if ($this->math->compare((string) $cycle->tolls_amount, '0.000000') > 0) {
            $srcLineId = ($sourceId * 1000) + $lineNumber;
            $lines[] = new InvoiceLineData(
                lineNumber: $lineNumber,
                description: 'Tolls and Highway Charges',
                quantity: self::WHOLE_CHARGE,
                unitPrice: (string) $cycle->tolls_amount,
                lineType: InvoiceLineType::Charge,
                sourceLineType: $sourceType,
                sourceLineId: $srcLineId,
            );
            $sourceLines[] = new InvoiceSourceLineData(
                tenantId: $context->tenantId,
                organizationUnitId: $context->organizationUnitId,
                sourceType: $sourceType,
                sourceId: $sourceId,
                sourceLineType: $sourceType,
                sourceLineId: $srcLineId,
                sourceQuantity: self::WHOLE_CHARGE,
                invoicedQuantity: self::WHOLE_CHARGE,
                sourceUnitPrice: (string) $cycle->tolls_amount,
                sourceLineTotal: (string) $cycle->tolls_amount,
                invoicedLineTotal: (string) $cycle->tolls_amount,
            );
            $lineNumber++;
        }

        // 6. Ad-hoc Additions
        foreach ($cycle->items->where('item_type', BillingCycleItemType::Addition) as $item) {
            $veh = $item->vehicle?->registration_number ?? $item->vehicle?->vehicle_number;
            $drv = $item->driver?->display_name;
            $attr = array_filter([$veh ? "Vehicle: {$veh}" : null, $drv ? "Driver: {$drv}" : null]);
            $attrStr = ! empty($attr) ? ' ['.implode(', ', $attr).']' : '';
            $desc = "Addition: {$item->description} ({$item->category->value}){$attrStr}";
            $srcLineId = ($sourceId * 1000) + $lineNumber;

            $lines[] = new InvoiceLineData(
                lineNumber: $lineNumber,
                description: $desc,
                quantity: self::WHOLE_CHARGE,
                unitPrice: (string) $item->amount,
                lineType: InvoiceLineType::Charge,
                sourceLineType: $sourceType,
                sourceLineId: $srcLineId,
            );
            $sourceLines[] = new InvoiceSourceLineData(
                tenantId: $context->tenantId,
                organizationUnitId: $context->organizationUnitId,
                sourceType: $sourceType,
                sourceId: $sourceId,
                sourceLineType: $sourceType,
                sourceLineId: $srcLineId,
                sourceQuantity: self::WHOLE_CHARGE,
                invoicedQuantity: self::WHOLE_CHARGE,
                sourceUnitPrice: (string) $item->amount,
                sourceLineTotal: (string) $item->amount,
                invoicedLineTotal: (string) $item->amount,
            );
            $lineNumber++;
        }

        // 7. Apply Ad-hoc Deductions as discounts against charges
        $deductions = $cycle->items->where('item_type', BillingCycleItemType::Deduction);
        $applied = $this->applyDeductionsAsDiscounts($lines, $sourceLines, $deductions);
        $lines = $applied['lines'];
        $sourceLines = $applied['sourceLines'];

        // Fallback: if lines are empty but deltaAmount > 0
        if (empty($lines) && $this->math->compare($deltaAmount, '0.000000') > 0) {
            $srcLineId = ($sourceId * 1000) + 1;
            $lines[] = new InvoiceLineData(
                lineNumber: 1,
                description: "Supplementary Rental Billing · Cycle: {$cycle->cycle_number} · ({$periodLabel})",
                quantity: self::WHOLE_CHARGE,
                unitPrice: $deltaAmount,
                lineType: InvoiceLineType::Charge,
                sourceLineType: $sourceType,
                sourceLineId: $srcLineId,
            );
            $sourceLines[] = new InvoiceSourceLineData(
                tenantId: $context->tenantId,
                organizationUnitId: $context->organizationUnitId,
                sourceType: $sourceType,
                sourceId: $sourceId,
                sourceLineType: $sourceType,
                sourceLineId: $srcLineId,
                sourceQuantity: self::WHOLE_CHARGE,
                invoicedQuantity: self::WHOLE_CHARGE,
                sourceUnitPrice: $deltaAmount,
                sourceLineTotal: $deltaAmount,
                invoicedLineTotal: $deltaAmount,
            );
        }

        foreach ($cycle->charts as $c) {
            $vNum = $c->vehicle?->registration_number ?? $c->vehicle?->vehicle_number;
            if ($vNum) {
                $vehicleNumbers[] = $vNum;
            }
        }

        $referenceFields = [
            ['label' => 'Agreement Reference', 'value' => (string) $agreement->reference],
            ['label' => 'Cycle Number', 'value' => (string) $cycle->cycle_number],
            ['label' => 'Billing Period', 'value' => $periodLabel],
        ];
        if (! empty($vehicleNumbers)) {
            $referenceFields[] = ['label' => 'Assigned Vehicle(s)', 'value' => implode(', ', array_unique($vehicleNumbers))];
        }

        return [
            'lines' => $lines,
            'sourceLines' => $sourceLines,
            'referenceFields' => $referenceFields,
        ];
    }

    /**
     * @return array{
     *     lines: list<InvoiceLineData>,
     *     sourceLines: list<InvoiceSourceLineData>,
     *     referenceFields: list<array{label: string, value: string}>
     * }
     */
    private function buildConsolidatedLines(
        AgreementContext $context,
        $agreement,
        BillingCycle $cycle,
        string $totalAmount,
        string $sourceType,
        int $sourceId
    ): array {
        $cycle->loadMissing(['charts.vehicle', 'items.vehicle', 'items.driver']);
        $terms = $agreement?->terms ?? [];
        $periodLabel = $cycle->period_from?->format('Y-m-d').' To '.$cycle->period_until?->format('Y-m-d');

        // 1. Start with Base Rent lines from buildStage1Lines
        $stage1 = $this->buildStage1Lines(
            $context,
            $agreement,
            $cycle,
            (string) $cycle->base_rent_amount,
            $sourceType,
            $sourceId
        );

        $lines = $stage1['lines'];
        $sourceLines = $stage1['sourceLines'];
        $lineNumber = count($lines) + 1;

        // 2. Excess Kilometers
        if ($this->math->compare((string) $cycle->excess_km_amount, '0.000000') > 0) {
            $excessKmRate = (string) ($terms['excess_km_rate'] ?? '0.000000');
            $freeKm = (string) ($terms['included_km'] ?? $terms['free_km_allowance'] ?? '0.000000');
            $totalKm = '0.000000';
            foreach ($cycle->charts as $c) {
                $totalKm = $this->math->add($totalKm, (string) ($c->commercial_km ?? '0.000000'), 6);
            }
            $excessKm = $this->math->compare($totalKm, $freeKm) > 0 ? $this->math->sub($totalKm, $freeKm, 6) : '0.000000';
            $srcLineId = ($sourceId * 1000) + $lineNumber;

            $lines[] = new InvoiceLineData(
                lineNumber: $lineNumber,
                description: "Excess Mileage Charge · {$excessKm} km @ {$excessKmRate}/km (Included: {$freeKm} km)",
                quantity: self::WHOLE_CHARGE,
                unitPrice: (string) $cycle->excess_km_amount,
                lineType: InvoiceLineType::Charge,
                sourceLineType: $sourceType,
                sourceLineId: $srcLineId,
            );
            $sourceLines[] = new InvoiceSourceLineData(
                tenantId: $context->tenantId,
                organizationUnitId: $context->organizationUnitId,
                sourceType: $sourceType,
                sourceId: $sourceId,
                sourceLineType: $sourceType,
                sourceLineId: $srcLineId,
                sourceQuantity: self::WHOLE_CHARGE,
                invoicedQuantity: self::WHOLE_CHARGE,
                sourceUnitPrice: (string) $cycle->excess_km_amount,
                sourceLineTotal: (string) $cycle->excess_km_amount,
                invoicedLineTotal: (string) $cycle->excess_km_amount,
            );
            $lineNumber++;
        }

        // 3. Overtime Charges (Normal, Double, Triple)
        if ($this->math->compare((string) $cycle->overtime_amount, '0.000000') > 0) {
            $normalOtRate = (string) ($terms['normal_ot_rate'] ?? '0.000000');
            $doubleOtRate = (string) ($terms['double_ot_rate'] ?? '0.000000');
            $tripleOtRate = (string) ($terms['triple_ot_rate'] ?? '0.000000');

            $totNormalMin = (int) $cycle->charts->sum('normal_ot_minutes');
            $totDoubleMin = (int) $cycle->charts->sum('double_ot_minutes');
            $totTripleMin = (int) $cycle->charts->sum('triple_ot_minutes');

            $normalHours = $this->math->div((string) $totNormalMin, '60', 6);
            $doubleHours = $this->math->div((string) $totDoubleMin, '60', 6);
            $tripleHours = $this->math->div((string) $totTripleMin, '60', 6);

            $normalAmt = $this->math->mul($normalHours, $normalOtRate, 6);
            $doubleAmt = $this->math->mul($doubleHours, $doubleOtRate, 6);
            $tripleAmt = $this->math->mul($tripleHours, $tripleOtRate, 6);
            $itemizedOtSum = $this->math->add($this->math->add($normalAmt, $doubleAmt, 6), $tripleAmt, 6);

            if ($this->math->compare($normalAmt, '0.000000') > 0) {
                $srcLineId = ($sourceId * 1000) + $lineNumber;
                $lines[] = new InvoiceLineData(
                    lineNumber: $lineNumber,
                    description: "Driver Overtime · Normal (1.5x) · {$normalHours} hrs @ {$normalOtRate}/hr",
                    quantity: self::WHOLE_CHARGE,
                    unitPrice: $normalAmt,
                    lineType: InvoiceLineType::Charge,
                    sourceLineType: $sourceType,
                    sourceLineId: $srcLineId,
                );
                $sourceLines[] = new InvoiceSourceLineData(
                    tenantId: $context->tenantId,
                    organizationUnitId: $context->organizationUnitId,
                    sourceType: $sourceType,
                    sourceId: $sourceId,
                    sourceLineType: $sourceType,
                    sourceLineId: $srcLineId,
                    sourceQuantity: self::WHOLE_CHARGE,
                    invoicedQuantity: self::WHOLE_CHARGE,
                    sourceUnitPrice: $normalAmt,
                    sourceLineTotal: $normalAmt,
                    invoicedLineTotal: $normalAmt,
                );
                $lineNumber++;
            }

            if ($this->math->compare($doubleAmt, '0.000000') > 0) {
                $srcLineId = ($sourceId * 1000) + $lineNumber;
                $lines[] = new InvoiceLineData(
                    lineNumber: $lineNumber,
                    description: "Driver Overtime · Double (2.0x) · {$doubleHours} hrs @ {$doubleOtRate}/hr",
                    quantity: self::WHOLE_CHARGE,
                    unitPrice: $doubleAmt,
                    lineType: InvoiceLineType::Charge,
                    sourceLineType: $sourceType,
                    sourceLineId: $srcLineId,
                );
                $sourceLines[] = new InvoiceSourceLineData(
                    tenantId: $context->tenantId,
                    organizationUnitId: $context->organizationUnitId,
                    sourceType: $sourceType,
                    sourceId: $sourceId,
                    sourceLineType: $sourceType,
                    sourceLineId: $srcLineId,
                    sourceQuantity: self::WHOLE_CHARGE,
                    invoicedQuantity: self::WHOLE_CHARGE,
                    sourceUnitPrice: $doubleAmt,
                    sourceLineTotal: $doubleAmt,
                    invoicedLineTotal: $doubleAmt,
                );
                $lineNumber++;
            }

            if ($this->math->compare($tripleAmt, '0.000000') > 0) {
                $srcLineId = ($sourceId * 1000) + $lineNumber;
                $lines[] = new InvoiceLineData(
                    lineNumber: $lineNumber,
                    description: "Driver Overtime · Triple (3.0x) · {$tripleHours} hrs @ {$tripleOtRate}/hr",
                    quantity: self::WHOLE_CHARGE,
                    unitPrice: $tripleAmt,
                    lineType: InvoiceLineType::Charge,
                    sourceLineType: $sourceType,
                    sourceLineId: $srcLineId,
                );
                $sourceLines[] = new InvoiceSourceLineData(
                    tenantId: $context->tenantId,
                    organizationUnitId: $context->organizationUnitId,
                    sourceType: $sourceType,
                    sourceId: $sourceId,
                    sourceLineType: $sourceType,
                    sourceLineId: $srcLineId,
                    sourceQuantity: self::WHOLE_CHARGE,
                    invoicedQuantity: self::WHOLE_CHARGE,
                    sourceUnitPrice: $tripleAmt,
                    sourceLineTotal: $tripleAmt,
                    invoicedLineTotal: $tripleAmt,
                );
                $lineNumber++;
            }

            $otRemainder = $this->math->sub((string) $cycle->overtime_amount, $itemizedOtSum, 6);
            if ($this->math->compare($otRemainder, '0.000000') > 0) {
                $srcLineId = ($sourceId * 1000) + $lineNumber;
                $lines[] = new InvoiceLineData(
                    lineNumber: $lineNumber,
                    description: 'Driver Overtime Service Charges',
                    quantity: self::WHOLE_CHARGE,
                    unitPrice: $otRemainder,
                    lineType: InvoiceLineType::Charge,
                    sourceLineType: $sourceType,
                    sourceLineId: $srcLineId,
                );
                $sourceLines[] = new InvoiceSourceLineData(
                    tenantId: $context->tenantId,
                    organizationUnitId: $context->organizationUnitId,
                    sourceType: $sourceType,
                    sourceId: $sourceId,
                    sourceLineType: $sourceType,
                    sourceLineId: $srcLineId,
                    sourceQuantity: self::WHOLE_CHARGE,
                    invoicedQuantity: self::WHOLE_CHARGE,
                    sourceUnitPrice: $otRemainder,
                    sourceLineTotal: $otRemainder,
                    invoicedLineTotal: $otRemainder,
                );
                $lineNumber++;
            }
        }

        // 4. Night-Out Allowance
        if ($this->math->compare((string) $cycle->night_out_amount, '0.000000') > 0) {
            $nightOuts = (int) $cycle->charts->sum('night_outs');
            $nightOutRate = (string) ($terms['night_out_rate'] ?? '0.000000');
            $srcLineId = ($sourceId * 1000) + $lineNumber;

            $lines[] = new InvoiceLineData(
                lineNumber: $lineNumber,
                description: "Driver Night-Out Allowance · {$nightOuts} Nights @ {$nightOutRate}",
                quantity: self::WHOLE_CHARGE,
                unitPrice: (string) $cycle->night_out_amount,
                lineType: InvoiceLineType::Charge,
                sourceLineType: $sourceType,
                sourceLineId: $srcLineId,
            );
            $sourceLines[] = new InvoiceSourceLineData(
                tenantId: $context->tenantId,
                organizationUnitId: $context->organizationUnitId,
                sourceType: $sourceType,
                sourceId: $sourceId,
                sourceLineType: $sourceType,
                sourceLineId: $srcLineId,
                sourceQuantity: self::WHOLE_CHARGE,
                invoicedQuantity: self::WHOLE_CHARGE,
                sourceUnitPrice: (string) $cycle->night_out_amount,
                sourceLineTotal: (string) $cycle->night_out_amount,
                invoicedLineTotal: (string) $cycle->night_out_amount,
            );
            $lineNumber++;
        }

        // 5. Tolls
        if ($this->math->compare((string) $cycle->tolls_amount, '0.000000') > 0) {
            $srcLineId = ($sourceId * 1000) + $lineNumber;
            $lines[] = new InvoiceLineData(
                lineNumber: $lineNumber,
                description: 'Tolls and Highway Charges',
                quantity: self::WHOLE_CHARGE,
                unitPrice: (string) $cycle->tolls_amount,
                lineType: InvoiceLineType::Charge,
                sourceLineType: $sourceType,
                sourceLineId: $srcLineId,
            );
            $sourceLines[] = new InvoiceSourceLineData(
                tenantId: $context->tenantId,
                organizationUnitId: $context->organizationUnitId,
                sourceType: $sourceType,
                sourceId: $sourceId,
                sourceLineType: $sourceType,
                sourceLineId: $srcLineId,
                sourceQuantity: self::WHOLE_CHARGE,
                invoicedQuantity: self::WHOLE_CHARGE,
                sourceUnitPrice: (string) $cycle->tolls_amount,
                sourceLineTotal: (string) $cycle->tolls_amount,
                invoicedLineTotal: (string) $cycle->tolls_amount,
            );
            $lineNumber++;
        }

        // 6. Ad-hoc Additions
        foreach ($cycle->items->where('item_type', BillingCycleItemType::Addition) as $item) {
            $veh = $item->vehicle?->registration_number ?? $item->vehicle?->vehicle_number;
            $drv = $item->driver?->display_name;
            $attr = array_filter([$veh ? "Vehicle: {$veh}" : null, $drv ? "Driver: {$drv}" : null]);
            $attrStr = ! empty($attr) ? ' ['.implode(', ', $attr).']' : '';
            $desc = "Addition: {$item->description} ({$item->category->value}){$attrStr}";
            $srcLineId = ($sourceId * 1000) + $lineNumber;

            $lines[] = new InvoiceLineData(
                lineNumber: $lineNumber,
                description: $desc,
                quantity: self::WHOLE_CHARGE,
                unitPrice: (string) $item->amount,
                lineType: InvoiceLineType::Charge,
                sourceLineType: $sourceType,
                sourceLineId: $srcLineId,
            );
            $sourceLines[] = new InvoiceSourceLineData(
                tenantId: $context->tenantId,
                organizationUnitId: $context->organizationUnitId,
                sourceType: $sourceType,
                sourceId: $sourceId,
                sourceLineType: $sourceType,
                sourceLineId: $srcLineId,
                sourceQuantity: self::WHOLE_CHARGE,
                invoicedQuantity: self::WHOLE_CHARGE,
                sourceUnitPrice: (string) $item->amount,
                sourceLineTotal: (string) $item->amount,
                invoicedLineTotal: (string) $item->amount,
            );
            $lineNumber++;
        }

        // 7. Apply Ad-hoc Deductions as discounts against charges
        $deductions = $cycle->items->where('item_type', BillingCycleItemType::Deduction);
        $applied = $this->applyDeductionsAsDiscounts($lines, $sourceLines, $deductions);
        $lines = $applied['lines'];
        $sourceLines = $applied['sourceLines'];

        // Fallback: if lines are empty but totalAmount > 0
        if (empty($lines) && $this->math->compare($totalAmount, '0.000000') > 0) {
            $srcLineId = ($sourceId * 1000) + 1;
            $lines[] = new InvoiceLineData(
                lineNumber: 1,
                description: "Consolidated Revised Rental Invoice · Cycle: {$cycle->cycle_number} · ({$periodLabel})",
                quantity: self::WHOLE_CHARGE,
                unitPrice: $totalAmount,
                lineType: InvoiceLineType::Charge,
                sourceLineType: $sourceType,
                sourceLineId: $srcLineId,
            );
            $sourceLines[] = new InvoiceSourceLineData(
                tenantId: $context->tenantId,
                organizationUnitId: $context->organizationUnitId,
                sourceType: $sourceType,
                sourceId: $sourceId,
                sourceLineType: $sourceType,
                sourceLineId: $srcLineId,
                sourceQuantity: self::WHOLE_CHARGE,
                invoicedQuantity: self::WHOLE_CHARGE,
                sourceUnitPrice: $totalAmount,
                sourceLineTotal: $totalAmount,
                invoicedLineTotal: $totalAmount,
            );
        }

        $vehicleNumbers = [];
        foreach ($cycle->charts as $c) {
            $vNum = $c->vehicle?->registration_number ?? $c->vehicle?->vehicle_number;
            if ($vNum) {
                $vehicleNumbers[] = $vNum;
            }
        }

        $referenceFields = [
            ['label' => 'Agreement Reference', 'value' => (string) $agreement->reference],
            ['label' => 'Cycle Number', 'value' => (string) $cycle->cycle_number],
            ['label' => 'Billing Period', 'value' => $periodLabel],
            ['label' => 'Invoice Mode', 'value' => 'Consolidated Final Revision'],
        ];
        if (! empty($vehicleNumbers)) {
            $referenceFields[] = ['label' => 'Assigned Vehicle(s)', 'value' => implode(', ', array_unique($vehicleNumbers))];
        }

        return [
            'lines' => $lines,
            'sourceLines' => $sourceLines,
            'referenceFields' => $referenceFields,
        ];
    }

    /**
     * @return array{
     *     lines: list<InvoiceLineData>,
     *     sourceLines: list<InvoiceSourceLineData>,
     *     referenceFields: list<array{label: string, value: string}>
     * }
     */
    private function buildCreditNoteLines(
        AgreementContext $context,
        $agreement,
        BillingCycle $cycle,
        string $amount,
        string $sourceType,
        int $sourceId,
        string $notes = ''
    ): array {
        $cycle->loadMissing(['items.vehicle', 'items.driver']);
        $periodLabel = $cycle->period_from?->format('Y-m-d').' To '.$cycle->period_until?->format('Y-m-d');
        $deductions = $cycle->items->where('item_type', BillingCycleItemType::Deduction);

        $lines = [];
        $sourceLines = [];
        $lineNumber = 1;

        $deductionsSum = '0.000000';
        foreach ($deductions as $d) {
            $deductionsSum = $this->math->add($deductionsSum, (string) $d->amount, 6);
        }

        if ($deductions->isNotEmpty()) {
            $remainingOffset = '0.000000';
            if ($this->math->compare($deductionsSum, $amount) > 0) {
                $remainingOffset = $this->math->sub($deductionsSum, $amount, 6);
            }

            foreach ($deductions as $item) {
                $veh = $item->vehicle?->registration_number ?? $item->vehicle?->vehicle_number;
                $drv = $item->driver?->display_name;
                $attr = array_filter([$veh ? "Vehicle: {$veh}" : null, $drv ? "Driver: {$drv}" : null]);
                $attrStr = ! empty($attr) ? ' ['.implode(', ', $attr).']' : '';

                $itemAmount = (string) $item->amount;
                $lineDiscount = '0.000000';
                if ($this->math->compare($remainingOffset, '0.000000') > 0) {
                    $lineDiscount = $this->math->compare($remainingOffset, $itemAmount) >= 0 ? $itemAmount : $remainingOffset;
                    $remainingOffset = $this->math->sub($remainingOffset, $lineDiscount, 6);
                }

                $offsetDesc = $this->math->compare($lineDiscount, '0.000000') > 0 ? ' [Offset by usage: '.$lineDiscount.']' : '';
                $desc = "Credit Adjustment: {$item->description} ({$item->category->value}){$attrStr}{$offsetDesc}";
                $srcLineId = ($sourceId * 1000) + $lineNumber;

                $lines[] = new InvoiceLineData(
                    lineNumber: $lineNumber,
                    description: $desc,
                    quantity: self::WHOLE_CHARGE,
                    unitPrice: $itemAmount,
                    lineType: InvoiceLineType::Charge,
                    discountAmount: $lineDiscount,
                    sourceLineType: $sourceType,
                    sourceLineId: $srcLineId,
                );
                $sourceLines[] = new InvoiceSourceLineData(
                    tenantId: $context->tenantId,
                    organizationUnitId: $context->organizationUnitId,
                    sourceType: $sourceType,
                    sourceId: $sourceId,
                    sourceLineType: $sourceType,
                    sourceLineId: $srcLineId,
                    sourceQuantity: self::WHOLE_CHARGE,
                    invoicedQuantity: self::WHOLE_CHARGE,
                    sourceUnitPrice: $itemAmount,
                    sourceLineTotal: $itemAmount,
                    invoicedLineTotal: $this->math->sub($itemAmount, $lineDiscount, 6),
                );
                $lineNumber++;
            }

            if ($this->math->compare($amount, $deductionsSum) > 0) {
                $remainder = $this->math->sub($amount, $deductionsSum, 6);
                $srcLineId = ($sourceId * 1000) + $lineNumber;
                $desc = $notes ?: "Credit Adjustment: Net Difference · ({$periodLabel})";

                $lines[] = new InvoiceLineData(
                    lineNumber: $lineNumber,
                    description: $desc,
                    quantity: self::WHOLE_CHARGE,
                    unitPrice: $remainder,
                    lineType: InvoiceLineType::Charge,
                    discountAmount: '0.000000',
                    sourceLineType: $sourceType,
                    sourceLineId: $srcLineId,
                );
                $sourceLines[] = new InvoiceSourceLineData(
                    tenantId: $context->tenantId,
                    organizationUnitId: $context->organizationUnitId,
                    sourceType: $sourceType,
                    sourceId: $sourceId,
                    sourceLineType: $sourceType,
                    sourceLineId: $srcLineId,
                    sourceQuantity: self::WHOLE_CHARGE,
                    invoicedQuantity: self::WHOLE_CHARGE,
                    sourceUnitPrice: $remainder,
                    sourceLineTotal: $remainder,
                    invoicedLineTotal: $remainder,
                );
            }
        } else {
            $desc = $notes ?: "Credit Note · {$agreement->reference} · ({$periodLabel}) · Cycle: {$cycle->cycle_number}";
            $srcLineId = ($sourceId * 1000) + 1;

            $lines[] = new InvoiceLineData(
                lineNumber: 1,
                description: $desc,
                quantity: self::WHOLE_CHARGE,
                unitPrice: $amount,
                lineType: InvoiceLineType::Charge,
                sourceLineType: $sourceType,
                sourceLineId: $srcLineId,
            );
            $sourceLines[] = new InvoiceSourceLineData(
                tenantId: $context->tenantId,
                organizationUnitId: $context->organizationUnitId,
                sourceType: $sourceType,
                sourceId: $sourceId,
                sourceLineType: $sourceType,
                sourceLineId: $srcLineId,
                sourceQuantity: self::WHOLE_CHARGE,
                invoicedQuantity: self::WHOLE_CHARGE,
                sourceUnitPrice: $amount,
                sourceLineTotal: $amount,
                invoicedLineTotal: $amount,
            );
        }

        $referenceFields = [
            ['label' => 'Agreement Reference', 'value' => (string) $agreement->reference],
            ['label' => 'Cycle Number', 'value' => (string) $cycle->cycle_number],
            ['label' => 'Credit Reason', 'value' => $notes ?: 'Billing Cycle Adjustment / Downtime Rebate'],
            ['label' => 'Period', 'value' => $periodLabel],
        ];

        return [
            'lines' => $lines,
            'sourceLines' => $sourceLines,
            'referenceFields' => $referenceFields,
        ];
    }

    /**
     * @param  list<InvoiceLineData>  $lines
     * @param  list<InvoiceSourceLineData>  $sourceLines
     * @param  iterable<BillingCycleItem>  $deductionItems
     * @return array{lines: list<InvoiceLineData>, sourceLines: list<InvoiceSourceLineData>}
     */
    private function applyDeductionsAsDiscounts(array $lines, array $sourceLines, iterable $deductionItems): array
    {
        $remainingDeductions = '0.000000';
        $deductionDescriptions = [];
        foreach ($deductionItems as $item) {
            $remainingDeductions = $this->math->add($remainingDeductions, (string) $item->amount, 6);
            $deductionDescriptions[] = trim($item->description.' ('.$item->category->value.'): '.$item->amount);
        }

        if ($this->math->isZero($remainingDeductions) || empty($lines)) {
            return ['lines' => $lines, 'sourceLines' => $sourceLines];
        }

        $updatedLines = [];
        $updatedSourceLines = [];

        foreach ($lines as $idx => $line) {
            $sourceLine = $sourceLines[$idx];
            $available = $this->math->sub($line->unitPrice, $line->discountAmount, 6);

            if ($this->math->compare($remainingDeductions, '0.000000') > 0 && $this->math->compare($available, '0.000000') > 0) {
                $discountToApply = $this->math->compare($remainingDeductions, $available) >= 0 ? $available : $remainingDeductions;
                $newDiscount = $this->math->add($line->discountAmount, $discountToApply, 6);
                $remainingDeductions = $this->math->sub($remainingDeductions, $discountToApply, 6);

                $updatedDesc = $line->description.' [Less discount: '.$discountToApply.']';

                $updatedLines[] = new InvoiceLineData(
                    lineNumber: $line->lineNumber,
                    description: $updatedDesc,
                    quantity: $line->quantity,
                    unitPrice: $line->unitPrice,
                    lineType: $line->lineType,
                    itemId: $line->itemId,
                    uomId: $line->uomId,
                    discountAmount: $newDiscount,
                    taxAmount: $line->taxAmount,
                    chargeAmount: $line->chargeAmount,
                    lineTotal: null,
                    sourceLineType: $line->sourceLineType,
                    sourceLineId: $line->sourceLineId,
                    metadata: $line->metadata,
                );

                $updatedSourceLines[] = new InvoiceSourceLineData(
                    tenantId: $sourceLine->tenantId,
                    organizationUnitId: $sourceLine->organizationUnitId,
                    sourceType: $sourceLine->sourceType,
                    sourceId: $sourceLine->sourceId,
                    sourceLineType: $sourceLine->sourceLineType,
                    sourceLineId: $sourceLine->sourceLineId,
                    sourceQuantity: $sourceLine->sourceQuantity,
                    invoicedQuantity: $sourceLine->invoicedQuantity,
                    sourceUnitPrice: $sourceLine->sourceUnitPrice,
                    sourceLineTotal: $sourceLine->sourceLineTotal,
                    invoicedLineTotal: $this->math->sub($sourceLine->sourceLineTotal, $discountToApply, 6),
                    previouslyInvoicedQuantity: $sourceLine->previouslyInvoicedQuantity,
                );
            } else {
                $updatedLines[] = $line;
                $updatedSourceLines[] = $sourceLine;
            }
        }

        return ['lines' => $updatedLines, 'sourceLines' => $updatedSourceLines];
    }

    public function previewCustomerQuotation(AgreementContext $context, array $input): array
    {
        $this->authorization->assertBilling($context, AgreementKind::Customer);

        if (! empty($input['billing_cycle_id'])) {
            $cycleId = (int) $input['billing_cycle_id'];
            $cycle = BillingCycle::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->with(['customerAgreement.party', 'customerAgreement.vehicle', 'charts.runningChart', 'items'])
                ->findOrFail($cycleId);

            $finalStage = $this->previewFinalStage($context, $cycleId);
            $agreement = $cycle->customerAgreement;
            $terms = $agreement?->terms ?? [];
            $taxRate = (string) ($input['tax_rate'] ?? $terms['tax_rate'] ?? '0.000000');
            $taxAmount = '0.000000';
            if ($this->math->compare($taxRate, '0.000000') > 0) {
                $taxAmount = $this->math->div($this->math->mul((string) $cycle->net_subtotal, $taxRate, 6), '100.000000', 6);
            }
            $grandTotal = $this->math->add((string) $cycle->net_subtotal, $taxAmount, 6);

            $lineItems = [];
            $lineItems[] = [
                'line_type' => 'base_rent',
                'category' => 'base_rent',
                'description' => "Base Rental Rate ({$finalStage['period_from']} to {$finalStage['period_until']})",
                'quantity' => '1.000000',
                'unit_rate' => (string) $cycle->base_rent_amount,
                'amount' => (string) $cycle->base_rent_amount,
            ];
            if ($this->math->compare((string) $cycle->excess_km_amount, '0.000000') > 0) {
                $lineItems[] = [
                    'line_type' => 'usage',
                    'category' => 'excess_km',
                    'description' => 'Excess Mileage Charge',
                    'quantity' => '1.000000',
                    'unit_rate' => (string) $cycle->excess_km_amount,
                    'amount' => (string) $cycle->excess_km_amount,
                ];
            }
            if ($this->math->compare((string) $cycle->overtime_amount, '0.000000') > 0) {
                $lineItems[] = [
                    'line_type' => 'usage',
                    'category' => 'overtime',
                    'description' => 'Overtime (Multi-Tier OT)',
                    'quantity' => '1.000000',
                    'unit_rate' => (string) $cycle->overtime_amount,
                    'amount' => (string) $cycle->overtime_amount,
                ];
            }
            if ($this->math->compare((string) $cycle->night_out_amount, '0.000000') > 0) {
                $lineItems[] = [
                    'line_type' => 'usage',
                    'category' => 'night_out',
                    'description' => 'Driver Night Out Allowance',
                    'quantity' => '1.000000',
                    'unit_rate' => (string) $cycle->night_out_amount,
                    'amount' => (string) $cycle->night_out_amount,
                ];
            }
            if ($this->math->compare((string) $cycle->tolls_amount, '0.000000') > 0) {
                $lineItems[] = [
                    'line_type' => 'usage',
                    'category' => 'tolls',
                    'description' => 'Highway Toll Reimbursement',
                    'quantity' => '1.000000',
                    'unit_rate' => (string) $cycle->tolls_amount,
                    'amount' => (string) $cycle->tolls_amount,
                ];
            }
            foreach ($finalStage['line_items'] as $item) {
                $lineItems[] = [
                    'line_type' => $item['item_type'],
                    'category' => $item['category'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_rate' => $item['unit_rate'],
                    'amount' => $item['amount'],
                ];
            }

            $totalCustodyDays = 0;
            foreach ($finalStage['vehicle_attributions'] as $va) {
                $totalCustodyDays += (int) ($va['custody_days'] ?? 0);
            }
            if ($totalCustodyDays === 0) {
                $totalCustodyDays = max(1, $cycle->period_from->diffInDays($cycle->period_until) + 1);
            }

            $totalKm = '0.000000';
            $normalOtMins = 0;
            $doubleOtMins = 0;
            $tripleOtMins = 0;
            $nightOuts = 0;
            foreach ($finalStage['vehicle_attributions'] as $va) {
                $totalKm = $this->math->add($totalKm, (string) ($va['total_km'] ?? '0.000000'), 6);
                $normalOtMins += (int) ($va['normal_ot_minutes'] ?? 0);
                $doubleOtMins += (int) ($va['double_ot_minutes'] ?? 0);
                $tripleOtMins += (int) ($va['triple_ot_minutes'] ?? 0);
                $nightOuts += (int) ($va['night_outs'] ?? 0);
            }

            $normalOtHours = $this->math->div((string) $normalOtMins, '60', 6);
            $doubleOtHours = $this->math->div((string) $doubleOtMins, '60', 6);
            $tripleOtHours = $this->math->div((string) $tripleOtMins, '60', 6);

            return [
                'document_type' => 'customer_quotation',
                'is_preview' => true,
                'persisted' => false,
                'quotation_number' => 'QTN-CYCLE-'.$cycle->cycle_number,
                'agreement' => [
                    'id' => $agreement?->id,
                    'reference' => $agreement?->reference ?? 'Customer Agreement',
                    'customer_name' => $agreement?->party?->name ?? 'Customer',
                    'customer_id' => $agreement?->party_id,
                    'vehicle_id' => $agreement?->vehicle_id,
                    'vehicle_number' => $agreement?->vehicle?->registration_number ?? $agreement?->vehicle?->vehicle_number ?? 'Fleet Vehicle',
                    'starts_on' => $agreement?->starts_on?->toDateString(),
                    'ends_on' => $agreement?->ends_on?->toDateString(),
                    'driver_mode' => $agreement?->driver_mode?->value ?? $agreement?->driver_mode,
                ],
                'period_from' => $cycle->period_from?->toDateString(),
                'period_until' => $cycle->period_until?->toDateString(),
                'custody_days' => $totalCustodyDays,
                'rates' => [
                    'base_rate' => (string) ($terms['base_rate'] ?? '0.000000'),
                    'excess_km_rate' => (string) ($terms['excess_km_rate'] ?? '0.000000'),
                    'normal_ot_rate' => (string) ($terms['normal_ot_rate'] ?? '0.000000'),
                    'double_ot_rate' => (string) ($terms['double_ot_rate'] ?? '0.000000'),
                    'triple_ot_rate' => (string) ($terms['triple_ot_rate'] ?? '0.000000'),
                    'night_out_rate' => (string) ($terms['night_out_rate'] ?? '0.000000'),
                    'tax_rate' => $taxRate,
                ],
                'telematics_summary' => [
                    'charts_count' => $finalStage['charts_count'] ?? 0,
                    'total_km' => $totalKm,
                    'excess_km' => (string) $cycle->excess_km_amount,
                    'normal_ot_hours' => $normalOtHours,
                    'double_ot_hours' => $doubleOtHours,
                    'triple_ot_hours' => $tripleOtHours,
                    'night_outs' => $nightOuts,
                    'tolls_amount' => (string) $cycle->tolls_amount,
                ],
                'breakdown' => [
                    'base_rent_amount' => (string) $cycle->base_rent_amount,
                    'excess_km_amount' => (string) $cycle->excess_km_amount,
                    'normal_ot_amount' => (string) $cycle->overtime_amount,
                    'double_ot_amount' => '0.000000',
                    'triple_ot_amount' => '0.000000',
                    'overtime_amount' => (string) $cycle->overtime_amount,
                    'night_out_amount' => (string) $cycle->night_out_amount,
                    'tolls_amount' => (string) $cycle->tolls_amount,
                    'additions_amount' => (string) $cycle->additions_amount,
                    'deductions_amount' => (string) $cycle->deductions_amount,
                    'subtotal' => (string) $cycle->net_subtotal,
                    'tax_amount' => $taxAmount,
                    'grand_total' => $grandTotal,
                ],
                'line_items' => $lineItems,
                'vehicle_attributions' => $finalStage['vehicle_attributions'] ?? [],
                'driver_attributions' => $finalStage['driver_attributions'] ?? [],
            ];
        }

        $agreementId = (int) ($input['agreement_id'] ?? $input['customer_agreement_id'] ?? 0);
        if ($agreementId <= 0) {
            throw ValidationException::withMessages(['agreement_id' => ['Please select a customer agreement.']]);
        }

        $agreement = CustomerAgreement::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->with(['party', 'vehicle'])
            ->findOrFail($agreementId);

        $periodFrom = (string) ($input['period_from'] ?? $agreement->starts_on?->toDateString() ?? now()->toDateString());
        $periodUntil = (string) ($input['period_until'] ?? now()->toDateString());

        $from = CarbonImmutable::parse($periodFrom)->startOfDay();
        $until = CarbonImmutable::parse($periodUntil)->endOfDay();
        if ($until->lessThan($from)) {
            throw ValidationException::withMessages(['period_until' => ['Period until must be on or after period from.']]);
        }
        $periodDays = max(1, $from->diffInDays($until) + 1);

        $terms = $agreement->terms ?? [];
        $baseRate = (string) ($terms['base_rate'] ?? '0.000000');
        $normalOtRate = (string) ($terms['normal_ot_rate'] ?? $terms['ot_rate'] ?? '0.000000');
        $doubleOtRate = (string) ($terms['double_ot_rate'] ?? '0.000000');
        if ($this->math->compare($doubleOtRate, '0.000000') === 0 && $this->math->compare($normalOtRate, '0.000000') > 0) {
            $doubleOtRate = $this->math->mul($this->math->div($normalOtRate, '1.5', 6), '2.0', 6);
        }
        $tripleOtRate = (string) ($terms['triple_ot_rate'] ?? '0.000000');
        if ($this->math->compare($tripleOtRate, '0.000000') === 0 && $this->math->compare($normalOtRate, '0.000000') > 0) {
            $tripleOtRate = $this->math->mul($this->math->div($normalOtRate, '1.5', 6), '3.0', 6);
        }
        $nightOutRate = (string) ($terms['night_out_rate'] ?? '0.000000');
        $excessKmRate = (string) ($terms['excess_km_rate'] ?? '0.000000');
        $freeKmAllowance = (string) ($terms['included_km'] ?? $terms['free_km_allowance'] ?? '0.000000');
        $taxRate = (string) ($input['tax_rate'] ?? $terms['tax_rate'] ?? '0.000000');

        // Base rent calculation
        $baseRent = '0.000000';
        try {
            $preview = $this->baseRentPreview->calculate(
                AgreementKind::Customer,
                $context,
                $agreement->id,
                [
                    'policy' => BaseRentPolicy::ActualCalendarDays->value,
                    'expected_version' => (int) $agreement->row_version,
                    'from' => $periodFrom,
                    'until' => $periodUntil,
                ]
            );
            $baseRent = (string) ($preview['base_rent'] ?? $preview['total'] ?? $preview['amount'] ?? '0.000000');
        } catch (\Throwable) {
            if ($agreement->basis === RentalBasis::Daily) {
                $baseRent = $this->math->mul($baseRate, (string) $periodDays, 6);
            } else {
                $baseRent = $this->math->div($this->math->mul($baseRate, (string) $periodDays, 6), '30.000000', 6);
            }
        }

        // Custody calculation from VehicleUse
        $uses = VehicleUse::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->where('customer_agreement_id', $agreement->id)
            ->where('starts_at', '<=', $until)
            ->where(function ($q) use ($from) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $from)
                    ->orWhere('returned_at', '>=', $from);
            })
            ->where('status', '!=', VehicleUseStatus::Cancelled->value)
            ->with('vehicle')
            ->orderBy('starts_at')
            ->get();

        $totalCustodyDays = 0;
        $custodyMap = [];
        $useCount = $uses->count();
        foreach ($uses as $index => $use) {
            $handoverTime = $use->handed_over_at ?? $use->starts_at;
            $returnTime = $use->returned_at ?? $use->ends_at;

            $startStr = $handoverTime?->toDateString() ?? $periodFrom;
            $endStr = $returnTime?->toDateString() ?? $periodUntil;

            $uStartDay = CarbonImmutable::parse(max($startStr, $periodFrom))->startOfDay();
            $uEndDay = CarbonImmutable::parse(min($endStr, $periodUntil))->startOfDay();

            if ($uStartDay->greaterThan($uEndDay)) {
                $days = 0;
            } else {
                $hasNextConsecutive = false;
                if ($index < $useCount - 1) {
                    $nextUse = $uses[$index + 1];
                    $nextHandover = ($nextUse->handed_over_at ?? $nextUse->starts_at)?->toDateString() ?? $periodFrom;
                    $nextStartDay = CarbonImmutable::parse(max($nextHandover, $periodFrom))->startOfDay();
                    if (($nextStartDay->equalTo($uEndDay) && $nextStartDay->greaterThan($uStartDay))
                        || ($nextUse->replaces_use_id === $use->id && $nextStartDay->lessThanOrEqualTo($uEndDay) && $nextStartDay->greaterThan($uStartDay))) {
                        $hasNextConsecutive = true;
                    }
                }

                if ($hasNextConsecutive) {
                    $days = (int) min($periodDays, $uStartDay->diffInDays($uEndDay));
                } else {
                    $days = (int) min($periodDays, $uStartDay->diffInDays($uEndDay) + 1);
                }
            }

            if ($days > 0) {
                $custodyMap[$use->vehicle_id] = ($custodyMap[$use->vehicle_id] ?? 0) + $days;
                $totalCustodyDays += $days;
            }
        }

        if ($totalCustodyDays === 0) {
            $totalCustodyDays = $periodDays;
            if ($agreement->vehicle_id) {
                $custodyMap[$agreement->vehicle_id] = $periodDays;
            }
        }

        // Running charts
        $includeCharts = (bool) ($input['include_running_charts'] ?? true);
        $charts = collect();
        if ($includeCharts) {
            $chartQuery = RunningChart::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->whereHas('vehicleUse', fn ($q) => $q->where('customer_agreement_id', $agreement->id));

            if (! empty($input['running_chart_ids'])) {
                $chartQuery->whereIn('id', (array) $input['running_chart_ids']);
            } else {
                $chartQuery->where('starts_at', '<=', $until)
                    ->where('ends_at', '>=', $from);
            }
            $charts = $chartQuery->with(['vehicleUse.vehicle'])->get();
        }

        $totalCommercialKm = '0.000000';
        $totalNormalOtMinutes = 0;
        $totalDoubleOtMinutes = 0;
        $totalTripleOtMinutes = 0;
        $totalNightOuts = 0;

        foreach ($charts as $chart) {
            $totalCommercialKm = $this->math->add($totalCommercialKm, (string) ($chart->commercial_km ?? '0.000000'), 6);
            $totalNormalOtMinutes += (int) ($chart->normal_ot_minutes ?? 0);
            $totalDoubleOtMinutes += (int) ($chart->double_ot_minutes ?? 0);
            $totalTripleOtMinutes += (int) ($chart->triple_ot_minutes ?? 0);
            $totalNightOuts += (int) ($chart->night_outs ?? 0);
        }

        // Tolls
        $chartIds = $charts->pluck('id')->all();
        $useIds = $charts->pluck('vehicle_use_id')->filter()->unique()->all();
        $totalTolls = '0.000000';
        if (! empty($chartIds) || ! empty($useIds)) {
            $tollExpenses = RentalExpense::query()
                ->forTenant($context->tenantId)
                ->where(function (Builder $q) use ($chartIds, $useIds) {
                    if (! empty($chartIds)) {
                        $q->whereIn('running_chart_id', $chartIds);
                    }
                    if (! empty($useIds)) {
                        $q->orWhere(function (Builder $sub) use ($useIds) {
                            $sub->whereIn('vehicle_use_id', $useIds)->whereNull('running_chart_id');
                        });
                    }
                })
                ->where('expense_type', RentalExpenseType::Toll)
                ->whereNotIn('status', [RentalExpenseStatus::Rejected, RentalExpenseStatus::Reversed])
                ->get();

            foreach ($tollExpenses as $te) {
                $amt = (string) ($te->gross_amount ?? $te->net_amount ?? '0.000000');
                $totalTolls = $this->math->add($totalTolls, $amt, 6);
            }
        }

        // Excess KM
        $excessKm = '0.000000';
        if ($this->math->compare($totalCommercialKm, $freeKmAllowance) > 0) {
            $excessKm = $this->math->sub($totalCommercialKm, $freeKmAllowance, 6);
        }
        $excessKmAmount = $this->math->mul($excessKm, $excessKmRate, 6);

        // Multi-tier OT
        $normalOtHours = $this->math->div((string) $totalNormalOtMinutes, '60', 6);
        $normalOtAmount = $this->math->mul($normalOtHours, $normalOtRate, 6);

        $doubleOtHours = $this->math->div((string) $totalDoubleOtMinutes, '60', 6);
        $doubleOtAmount = $this->math->mul($doubleOtHours, $doubleOtRate, 6);

        $tripleOtHours = $this->math->div((string) $totalTripleOtMinutes, '60', 6);
        $tripleOtAmount = $this->math->mul($tripleOtHours, $tripleOtRate, 6);

        $overtimeAmount = $this->math->add($normalOtAmount, $doubleOtAmount, 6);
        $overtimeAmount = $this->math->add($overtimeAmount, $tripleOtAmount, 6);

        // Night out
        $nightOutAmount = $this->math->mul((string) $totalNightOuts, $nightOutRate, 6);

        // Additions & Deductions
        $additionsAmount = '0.000000';
        $deductionsAmount = '0.000000';
        $lineItems = [];

        $lineItems[] = [
            'line_type' => 'base_rent',
            'category' => 'base_rent',
            'description' => "Base Rental Rate ({$periodFrom} to {$periodUntil})",
            'quantity' => (string) $totalCustodyDays,
            'unit_rate' => $agreement->basis === RentalBasis::Daily ? $baseRate : $baseRent,
            'amount' => $baseRent,
        ];

        if ($this->math->compare($excessKmAmount, '0.000000') > 0) {
            $lineItems[] = [
                'line_type' => 'usage',
                'category' => 'excess_km',
                'description' => "Excess Mileage ({$excessKm} km @ {$excessKmRate}/km)",
                'quantity' => $excessKm,
                'unit_rate' => $excessKmRate,
                'amount' => $excessKmAmount,
            ];
        }

        if ($this->math->compare($normalOtAmount, '0.000000') > 0) {
            $lineItems[] = [
                'line_type' => 'usage',
                'category' => 'overtime_normal',
                'description' => "Normal Overtime 1.5x ({$normalOtHours} hrs @ {$normalOtRate}/hr)",
                'quantity' => $normalOtHours,
                'unit_rate' => $normalOtRate,
                'amount' => $normalOtAmount,
            ];
        }

        if ($this->math->compare($doubleOtAmount, '0.000000') > 0) {
            $lineItems[] = [
                'line_type' => 'usage',
                'category' => 'overtime_double',
                'description' => "Double Overtime 2.0x ({$doubleOtHours} hrs @ {$doubleOtRate}/hr)",
                'quantity' => $doubleOtHours,
                'unit_rate' => $doubleOtRate,
                'amount' => $doubleOtAmount,
            ];
        }

        if ($this->math->compare($tripleOtAmount, '0.000000') > 0) {
            $lineItems[] = [
                'line_type' => 'usage',
                'category' => 'overtime_triple',
                'description' => "Triple Overtime 3.0x ({$tripleOtHours} hrs @ {$tripleOtRate}/hr)",
                'quantity' => $tripleOtHours,
                'unit_rate' => $tripleOtRate,
                'amount' => $tripleOtAmount,
            ];
        }

        if ($this->math->compare($nightOutAmount, '0.000000') > 0) {
            $lineItems[] = [
                'line_type' => 'usage',
                'category' => 'night_out',
                'description' => "Driver Night Out Allowance ({$totalNightOuts} nights @ {$nightOutRate})",
                'quantity' => (string) $totalNightOuts,
                'unit_rate' => $nightOutRate,
                'amount' => $nightOutAmount,
            ];
        }

        if ($this->math->compare($totalTolls, '0.000000') > 0) {
            $lineItems[] = [
                'line_type' => 'usage',
                'category' => 'tolls',
                'description' => 'Highway Toll Reimbursement',
                'quantity' => '1.000000',
                'unit_rate' => $totalTolls,
                'amount' => $totalTolls,
            ];
        }

        if (! empty($input['additions']) && is_array($input['additions'])) {
            foreach ($input['additions'] as $add) {
                $amt = (string) ($add['amount'] ?? '0.000000');
                $additionsAmount = $this->math->add($additionsAmount, $amt, 6);
                $lineItems[] = [
                    'line_type' => 'addition',
                    'category' => (string) ($add['category'] ?? 'other'),
                    'description' => (string) ($add['description'] ?? 'Quotation Addition'),
                    'quantity' => (string) ($add['quantity'] ?? '1.000000'),
                    'unit_rate' => (string) ($add['unit_rate'] ?? $amt),
                    'amount' => $amt,
                ];
            }
        }

        if (! empty($input['deductions']) && is_array($input['deductions'])) {
            foreach ($input['deductions'] as $ded) {
                $amt = (string) ($ded['amount'] ?? '0.000000');
                $deductionsAmount = $this->math->add($deductionsAmount, $amt, 6);
                $lineItems[] = [
                    'line_type' => 'deduction',
                    'category' => (string) ($ded['category'] ?? 'discount'),
                    'description' => (string) ($ded['description'] ?? 'Quotation Discount / Deduction'),
                    'quantity' => (string) ($ded['quantity'] ?? '1.000000'),
                    'unit_rate' => (string) ($ded['unit_rate'] ?? $amt),
                    'amount' => $amt,
                ];
            }
        }

        // Agreement adjustments
        $adjustments = AgreementAdjustment::query()
            ->where('tenant_id', $context->tenantId)
            ->where('organization_unit_id', $context->organizationUnitId)
            ->where('customer_agreement_id', $agreement->id)
            ->where('status', AgreementAdjustmentStatus::Authorized->value)
            ->whereNull('invoice_id')
            ->get();

        foreach ($adjustments as $adj) {
            $adjAmt = (string) $adj->amount;
            if ($adj->adjustment_type === AgreementAdjustmentType::DebitNote) {
                $additionsAmount = $this->math->add($additionsAmount, $adjAmt, 6);
                $lineItems[] = [
                    'line_type' => 'addition',
                    'category' => 'adjustment',
                    'description' => "Adjustment Debit Note · {$adj->reference}: {$adj->reason}",
                    'quantity' => '1.000000',
                    'unit_rate' => $adjAmt,
                    'amount' => $adjAmt,
                ];
            } else {
                $deductionsAmount = $this->math->add($deductionsAmount, $adjAmt, 6);
                $lineItems[] = [
                    'line_type' => 'deduction',
                    'category' => 'discount',
                    'description' => "Adjustment Credit Note / Discount · {$adj->reference}: {$adj->reason}",
                    'quantity' => '1.000000',
                    'unit_rate' => $adjAmt,
                    'amount' => $adjAmt,
                ];
            }
        }

        $subtotal = $this->math->add($baseRent, $excessKmAmount, 6);
        $subtotal = $this->math->add($subtotal, $overtimeAmount, 6);
        $subtotal = $this->math->add($subtotal, $nightOutAmount, 6);
        $subtotal = $this->math->add($subtotal, $totalTolls, 6);
        $subtotal = $this->math->add($subtotal, $additionsAmount, 6);
        $subtotal = $this->math->compare($subtotal, $deductionsAmount) > 0
            ? $this->math->sub($subtotal, $deductionsAmount, 6)
            : '0.000000';

        $taxAmount = '0.000000';
        if ($this->math->compare($taxRate, '0.000000') > 0) {
            $taxAmount = $this->math->div($this->math->mul($subtotal, $taxRate, 6), '100.000000', 6);
        }
        $grandTotal = $this->math->add($subtotal, $taxAmount, 6);

        // Vehicle attributions
        $vehicleAttributions = [];
        $chartsByVehicle = $charts->groupBy(fn ($c) => $c->vehicleUse?->vehicle_id ?? $agreement->vehicle_id ?? 0);
        if ($chartsByVehicle->isNotEmpty()) {
            foreach ($chartsByVehicle as $vId => $vCharts) {
                $vVehicle = $vCharts->first()->vehicleUse?->vehicle ?? Vehicle::query()->find($vId);
                $regNo = $vVehicle?->registration_number ?? $vVehicle?->vehicle_number ?? "Vehicle #{$vId}";
                $label = $vVehicle ? trim("{$regNo} · {$vVehicle->make} {$vVehicle->model}") : $regNo;
                $vCustody = $custodyMap[$vId] ?? $totalCustodyDays;

                $vKm = '0.000000';
                $vNormalOt = 0;
                $vDoubleOt = 0;
                $vTripleOt = 0;
                $vNight = 0;
                foreach ($vCharts as $vc) {
                    $vKm = $this->math->add($vKm, (string) ($vc->commercial_km ?? '0.000000'), 6);
                    $vNormalOt += (int) ($vc->normal_ot_minutes ?? 0);
                    $vDoubleOt += (int) ($vc->double_ot_minutes ?? 0);
                    $vTripleOt += (int) ($vc->triple_ot_minutes ?? 0);
                    $vNight += (int) ($vc->night_outs ?? 0);
                }

                $vNormHours = $this->math->div((string) $vNormalOt, '60', 6);
                $vNormAmt = $this->math->mul($vNormHours, $normalOtRate, 6);
                $vDblHours = $this->math->div((string) $vDoubleOt, '60', 6);
                $vDblAmt = $this->math->mul($vDblHours, $doubleOtRate, 6);
                $vTrpHours = $this->math->div((string) $vTripleOt, '60', 6);
                $vTrpAmt = $this->math->mul($vTrpHours, $tripleOtRate, 6);
                $vOt = $this->math->add($vNormAmt, $vDblAmt, 6);
                $vOt = $this->math->add($vOt, $vTrpAmt, 6);
                $vNightAmt = $this->math->mul((string) $vNight, $nightOutRate, 6);

                $vBase = ($chartsByVehicle->count() > 1 && $totalCustodyDays > 0)
                    ? $this->math->div($this->math->mul($baseRent, (string) $vCustody, 6), (string) $totalCustodyDays, 6)
                    : $baseRent;

                $vSub = $this->math->add($vBase, $vOt, 6);
                $vSub = $this->math->add($vSub, $vNightAmt, 6);

                $vehicleAttributions[] = [
                    'vehicle_id' => (int) $vId,
                    'vehicle_number' => $regNo,
                    'vehicle_label' => $label,
                    'custody_days' => $vCustody,
                    'total_km' => $vKm,
                    'normal_ot_hours' => $vNormHours,
                    'double_ot_hours' => $vDblHours,
                    'triple_ot_hours' => $vTrpHours,
                    'night_outs' => $vNight,
                    'allocated_base_rent' => $vBase,
                    'allocated_ot_amount' => $vOt,
                    'allocated_night_out_amount' => $vNightAmt,
                    'subtotal' => $vSub,
                ];
            }
        } else {
            $vehicleNo = $agreement->vehicle?->registration_number ?? $agreement->vehicle?->vehicle_number ?? 'Fleet Vehicle';
            $vehicleAttributions[] = [
                'vehicle_id' => $agreement->vehicle_id,
                'vehicle_number' => $vehicleNo,
                'vehicle_label' => $vehicleNo,
                'custody_days' => $totalCustodyDays,
                'total_km' => '0.000000',
                'normal_ot_hours' => '0.000000',
                'double_ot_hours' => '0.000000',
                'triple_ot_hours' => '0.000000',
                'night_outs' => 0,
                'allocated_base_rent' => $baseRent,
                'allocated_ot_amount' => '0.000000',
                'allocated_night_out_amount' => '0.000000',
                'subtotal' => $baseRent,
            ];
        }

        // Driver attributions
        $driverAttributions = [];
        $chartsByDriver = $charts->groupBy(fn ($c) => $c->driver_name_snapshot ?? $c->driver_observation ?? 'Assigned Driver');
        foreach ($chartsByDriver as $dName => $dCharts) {
            $dKm = '0.000000';
            $dNorm = 0;
            $dDbl = 0;
            $dTrp = 0;
            $dNight = 0;
            $dEmpId = null;
            $dRef = '';
            foreach ($dCharts as $dc) {
                $dKm = $this->math->add($dKm, (string) ($dc->commercial_km ?? '0.000000'), 6);
                $dNorm += (int) ($dc->normal_ot_minutes ?? 0);
                $dDbl += (int) ($dc->double_ot_minutes ?? 0);
                $dTrp += (int) ($dc->triple_ot_minutes ?? 0);
                $dNight += (int) ($dc->night_outs ?? 0);
                if ($dc->driver_employee_id) {
                    $dEmpId = $dc->driver_employee_id;
                }
                if ($dc->driver_reference_snapshot) {
                    $dRef = $dc->driver_reference_snapshot;
                }
            }
            $dNormH = $this->math->div((string) $dNorm, '60', 6);
            $dNormAmt = $this->math->mul($dNormH, $normalOtRate, 6);
            $dDblH = $this->math->div((string) $dDbl, '60', 6);
            $dDblAmt = $this->math->mul($dDblH, $doubleOtRate, 6);
            $dTrpH = $this->math->div((string) $dTrp, '60', 6);
            $dTrpAmt = $this->math->mul($dTrpH, $tripleOtRate, 6);
            $dOt = $this->math->add($dNormAmt, $dDblAmt, 6);
            $dOt = $this->math->add($dOt, $dTrpAmt, 6);
            $dNightAmt = $this->math->mul((string) $dNight, $nightOutRate, 6);
            $dEarnings = $this->math->add($dOt, $dNightAmt, 6);

            $driverAttributions[] = [
                'driver_employee_id' => $dEmpId,
                'driver_name' => (string) $dName,
                'driver_reference' => $dRef,
                'charts_count' => $dCharts->count(),
                'total_km' => $dKm,
                'normal_ot_hours' => $dNormH,
                'normal_ot_amount' => $dNormAmt,
                'double_ot_hours' => $dDblH,
                'double_ot_amount' => $dDblAmt,
                'triple_ot_hours' => $dTrpH,
                'triple_ot_amount' => $dTrpAmt,
                'night_outs' => $dNight,
                'night_out_amount' => $dNightAmt,
                'total_earnings' => $dEarnings,
            ];
        }

        return [
            'document_type' => 'customer_quotation',
            'is_preview' => true,
            'persisted' => false,
            'quotation_number' => 'QTN-'.CarbonImmutable::parse($periodFrom)->format('Ymd').'-'.Str::upper(Str::random(4)),
            'agreement' => [
                'id' => $agreement->id,
                'reference' => $agreement->reference,
                'customer_name' => $agreement->party?->name ?? 'Customer',
                'customer_id' => $agreement->party_id,
                'vehicle_id' => $agreement->vehicle_id,
                'vehicle_number' => $agreement->vehicle?->registration_number ?? $agreement->vehicle?->vehicle_number ?? 'Fleet Vehicle',
                'starts_on' => $agreement->starts_on?->toDateString(),
                'ends_on' => $agreement->ends_on?->toDateString(),
                'driver_mode' => $agreement->driver_mode?->value ?? $agreement->driver_mode,
            ],
            'period_from' => $periodFrom,
            'period_until' => $periodUntil,
            'custody_days' => $totalCustodyDays,
            'rates' => [
                'base_rate' => $baseRate,
                'excess_km_rate' => $excessKmRate,
                'normal_ot_rate' => $normalOtRate,
                'double_ot_rate' => $doubleOtRate,
                'triple_ot_rate' => $tripleOtRate,
                'night_out_rate' => $nightOutRate,
                'tax_rate' => $taxRate,
            ],
            'telematics_summary' => [
                'charts_count' => $charts->count(),
                'total_km' => $totalCommercialKm,
                'excess_km' => $excessKm,
                'normal_ot_hours' => $normalOtHours,
                'double_ot_hours' => $doubleOtHours,
                'triple_ot_hours' => $tripleOtHours,
                'night_outs' => $totalNightOuts,
                'tolls_amount' => $totalTolls,
            ],
            'breakdown' => [
                'base_rent_amount' => $baseRent,
                'excess_km_amount' => $excessKmAmount,
                'normal_ot_amount' => $normalOtAmount,
                'double_ot_amount' => $doubleOtAmount,
                'triple_ot_amount' => $tripleOtAmount,
                'overtime_amount' => $overtimeAmount,
                'night_out_amount' => $nightOutAmount,
                'tolls_amount' => $totalTolls,
                'additions_amount' => $additionsAmount,
                'deductions_amount' => $deductionsAmount,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
            ],
            'line_items' => $lineItems,
            'vehicle_attributions' => $vehicleAttributions,
            'driver_attributions' => $driverAttributions,
        ];
    }

    private function assertCycleVersion(BillingCycle $cycle, int $expectedVersion): void
    {
        if ((int) $cycle->row_version !== $expectedVersion) {
            throw new ConflictHttpException("Cycle version mismatch: expected {$expectedVersion}, currently {$cycle->row_version}.");
        }
    }
}
