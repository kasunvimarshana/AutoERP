<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
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
use Modules\VehicleRental\Enums\BaseRentPolicy;
use Modules\VehicleRental\Enums\BillingCycleItemCategory;
use Modules\VehicleRental\Enums\BillingCycleItemType;
use Modules\VehicleRental\Enums\BillingCycleMode;
use Modules\VehicleRental\Enums\BillingCycleStatus;
use Modules\VehicleRental\Enums\BillingFinalizationMode;
use Modules\VehicleRental\Enums\RunningChartStatus;
use Modules\VehicleRental\Enums\VehicleUseStatus;
use Modules\VehicleRental\Models\AgreementAdjustment;
use Modules\VehicleRental\Models\AgreementAdjustmentHistory;
use Modules\VehicleRental\Models\BillingCycle;
use Modules\VehicleRental\Models\BillingCycleChart;
use Modules\VehicleRental\Models\BillingCycleItem;
use Modules\VehicleRental\Models\CustomerAgreement;
use Modules\VehicleRental\Models\OwnerAgreement;
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
        $this->authorization->assertBilling($context, $kind, true);

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

            $cycle = new BillingCycle();
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
        $this->authorization->assertBilling($context, $kind, false);

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
            ->whereHas('billingCycle', fn ($q) => $q->where('status', BillingCycleStatus::Finalized))
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
            $this->authorization->assertBilling($context, $kind, true);

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

            // Assert none are already finalized in another billing cycle
            $alreadyFinalized = BillingCycleChart::query()
                ->where('tenant_id', $context->tenantId)
                ->where('billing_cycle_id', '!=', $cycle->id)
                ->whereIn('running_chart_id', $chartIds)
                ->whereHas('billingCycle', fn ($q) => $q->where('status', BillingCycleStatus::Finalized))
                ->exists();

            if ($alreadyFinalized) {
                throw ValidationException::withMessages(['chart_ids' => ['One or more running charts are already finalized in another billing cycle.']]);
            }

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

                $chartAttachment = new BillingCycleChart();
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
                $chartAttachment->tolls_amount = '0.000000';
                $chartAttachment->is_replacement = (bool) ($chart->vehicleUse?->replaces_use_id !== null);
                $chartAttachment->save();

                $totalCommercialKm = $this->math->add($totalCommercialKm, $commercialKm, 6);
                $totalNormalOtMinutes += $normalOt;
                $totalDoubleOtMinutes += $doubleOt;
                $totalTripleOtMinutes += $tripleOt;
                $totalNightOuts += $nightOuts;
            }

            // Rates from agreement terms (unconfigured terms resolve to no automatic charge: '0.000000')
            $freeKmAllowance = (string) ($terms['included_km'] ?? $terms['free_km_allowance'] ?? '0.000000');
            $excessKmRate = (string) ($terms['excess_km_rate'] ?? '0.000000');
            $normalOtRatePerHour = (string) ($terms['normal_ot_rate'] ?? '0.000000');
            $doubleOtRatePerHour = (string) ($terms['double_ot_rate'] ?? '0.000000');
            $tripleOtRatePerHour = (string) ($terms['triple_ot_rate'] ?? '0.000000');
            $nightOutRate = (string) ($terms['night_out_rate'] ?? '0.000000');

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
            $this->authorization->assertBilling($context, $kind, true);

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

            $item = new BillingCycleItem();
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
            $this->authorization->assertBilling($context, $kind, true);

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

        $kind = $cycle->customer_agreement_id !== null ? AgreementKind::Customer : AgreementKind::Owner;
        $this->authorization->assertBilling($context, $kind, false);

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
        foreach ($uses as $use) {
            $handoverTime = $use->handover_at ?? $use->starts_at;
            $returnTime = $use->returned_at ?? $use->ends_at;

            $startStr = $handoverTime?->toDateString() ?? $cycle->period_from->toDateString();
            $endStr = $returnTime?->toDateString() ?? $cycle->period_until->toDateString();

            $uStartDay = CarbonImmutable::parse(max($startStr, $cycle->period_from->toDateString()))->startOfDay();
            $uEndDay = CarbonImmutable::parse(min($endStr, $cycle->period_until->toDateString()))->startOfDay();

            if ($uStartDay->greaterThan($uEndDay)) {
                $days = 0;
            } else {
                $days = (int) min($cycleDays, $uStartDay->diffInDays($uEndDay) + 1);
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
                } elseif ($item->driver_employee_id === null && str_contains(strtolower($item->description), strtolower((string) $driverName))) {
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
            $this->authorization->assertBilling($context, $kind, true);

            if ($cycle->status === BillingCycleStatus::Finalized) {
                throw ValidationException::withMessages(['cycle' => ['Billing cycle is already finalized.']]);
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

        $net = $this->math->compare($gross, $deductions) > 0
            ? $this->math->sub($gross, $deductions, 6)
            : '0.000000';

        $cycle->additions_amount = $additions;
        $cycle->deductions_amount = $deductions;
        $cycle->net_subtotal = $net;
        $cycle->total_billed_amount = $net;
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
        $invoiceDate = (string) ($input['invoice_date'] ?? now()->toDateString());
        $dueDate = ! empty($input['due_date']) ? (string) $input['due_date'] : null;

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
        $invoiceDate = (string) ($input['invoice_date'] ?? now()->toDateString());
        $dueDate = ! empty($input['due_date']) ? (string) $input['due_date'] : null;

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
        $invoiceDate = (string) ($input['invoice_date'] ?? now()->toDateString());
        $dueDate = ! empty($input['due_date']) ? (string) $input['due_date'] : null;

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
                $reversalDate = now()->toDateString();
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
        $adjustment = new AgreementAdjustment();
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

        $history = new AgreementAdjustmentHistory();
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
        $invoiceDate = (string) ($input['invoice_date'] ?? now()->toDateString());
        $dueDate = ! empty($input['due_date']) ? (string) $input['due_date'] : null;

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
        foreach ($uses as $use) {
            $handoverTime = $use->handover_at ?? $use->starts_at;
            $returnTime = $use->returned_at ?? $use->ends_at;

            $startStr = $handoverTime?->toDateString() ?? $cycle->period_from->toDateString();
            $endStr = $returnTime?->toDateString() ?? $cycle->period_until->toDateString();

            $uStartDay = CarbonImmutable::parse(max($startStr, $cycle->period_from->toDateString()))->startOfDay();
            $uEndDay = CarbonImmutable::parse(min($endStr, $cycle->period_until->toDateString()))->startOfDay();

            if ($uStartDay->greaterThan($uEndDay)) {
                $days = 0;
            } else {
                $days = (int) min($cycleDays, $uStartDay->diffInDays($uEndDay) + 1);
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

        if ($deductions->isNotEmpty() && $this->math->compare($deductionsSum, $amount) === 0) {
            foreach ($deductions as $item) {
                $veh = $item->vehicle?->registration_number ?? $item->vehicle?->vehicle_number;
                $drv = $item->driver?->display_name;
                $attr = array_filter([$veh ? "Vehicle: {$veh}" : null, $drv ? "Driver: {$drv}" : null]);
                $attrStr = ! empty($attr) ? ' ['.implode(', ', $attr).']' : '';
                $desc = "Credit Adjustment: {$item->description} ({$item->category->value}){$attrStr}";
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

    private function assertCycleVersion(BillingCycle $cycle, int $expectedVersion): void
    {
        if ((int) $cycle->row_version !== $expectedVersion) {
            throw new ConflictHttpException("Cycle version mismatch: expected {$expectedVersion}, currently {$cycle->row_version}.");
        }
    }
}
