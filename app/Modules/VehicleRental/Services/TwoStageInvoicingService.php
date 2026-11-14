<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\Enums\FinanceAccountRoleCode;
use Modules\Finance\Enums\FinancePostingProfileCode;
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
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\Invoice\Services\InvoiceStatusService;
use Modules\Invoice\Services\TaxedSourceInvoiceFactory;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\Enums\AllocationStatus;
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
use Modules\VehicleRental\Models\AgreementAdjustment;
use Modules\VehicleRental\Models\AgreementAdjustmentHistory;
use Modules\VehicleRental\Models\BillingCycle;
use Modules\VehicleRental\Models\BillingCycleChart;
use Modules\VehicleRental\Models\BillingCycleItem;
use Modules\VehicleRental\Models\CustomerAgreement;
use Modules\VehicleRental\Models\OwnerAgreement;
use Modules\VehicleRental\Models\RunningChart;
use Modules\VehicleRental\Models\VehicleRentalReassignment;
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
        private readonly PaymentAllocationService $allocationService,
        private readonly PaymentAllocationReversalService $allocationReversalService,
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
            $cycleNumber = 'CYCLE-'.$agreement->reference.'-'.CarbonImmutable::parse($periodFrom)->format('Ym').'-'.str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT);

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

            // Verify agreement ownership
            foreach ($charts as $chart) {
                if ((int) ($chart->vehicleUse?->$foreignKey ?? 0) !== (int) $agreementId) {
                    throw ValidationException::withMessages(['chart_ids' => ['One or more running charts do not belong to this agreement.']]);
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
            $totalNightOuts = 0;
            $totalTolls = '0.000000';

            foreach ($charts as $chart) {
                $commercialKm = (string) ($chart->commercial_km ?? '0.000000');
                $normalOt = (int) ($chart->normal_ot_minutes ?? 0);
                $doubleOt = (int) ($chart->double_ot_minutes ?? 0);
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
                $chartAttachment->night_outs = $nightOuts;
                $chartAttachment->tolls_amount = '0.000000';
                $chartAttachment->is_replacement = (bool) ($chart->vehicleUse?->replaces_use_id !== null);
                $chartAttachment->save();

                $totalCommercialKm = $this->math->add($totalCommercialKm, $commercialKm, 6);
                $totalNormalOtMinutes += $normalOt;
                $totalDoubleOtMinutes += $doubleOt;
                $totalNightOuts += $nightOuts;
            }

            // Rates from agreement terms
            $freeKmAllowance = (string) ($terms['included_km'] ?? $terms['free_km_allowance'] ?? '0.000000');
            $excessKmRate = (string) ($terms['excess_km_rate'] ?? '0.000000');
            $normalOtRatePerHour = (string) ($terms['normal_ot_rate'] ?? '1500.000000');
            $doubleOtRatePerHour = (string) ($terms['double_ot_rate'] ?? '2000.000000');
            $nightOutRate = (string) ($terms['night_out_rate'] ?? '1000.000000');

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

            $overtimeAmount = $this->math->add($normalOtAmount, $doubleOtAmount, 6);
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

            $item = new BillingCycleItem();
            $item->tenant_id = $context->tenantId;
            $item->billing_cycle_id = $cycle->id;
            $item->item_type = $itemType;
            $item->category = $category;
            $item->description = (string) ($data['description'] ?? $category->value);
            $item->quantity = $quantity;
            $item->unit_rate = $unitRate;
            $item->amount = $amount;
            $item->save();

            $this->recalculateCycleTotals($cycle);
            $cycle->row_version += 1;
            $cycle->save();

            return $item;
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
            ->with(['charts.runningChart', 'charts.vehicle', 'items', 'initialInvoice.balance'])
            ->findOrFail($cycleId);

        $kind = $cycle->customer_agreement_id !== null ? AgreementKind::Customer : AgreementKind::Owner;
        $this->authorization->assertBilling($context, $kind, false);

        $agreement = $cycle->customerAgreement ?? $cycle->ownerAgreement;
        $grossDelta = $this->math->sub((string) $cycle->net_subtotal, (string) $cycle->base_rent_amount, 6);

        // 1. Vehicle Attributions
        $vehicleAttributions = [];
        $chartsByVehicle = $cycle->charts->groupBy('vehicle_id');
        $cycleDays = max(1, $cycle->period_from->diffInDays($cycle->period_until) + 1);

        foreach ($chartsByVehicle as $vehicleId => $vCharts) {
            $vehicle = $vCharts->first()->vehicle ?? Vehicle::query()->find($vehicleId);
            $regNo = $vehicle?->registration_number ?? $vehicle?->vehicle_number ?? "Vehicle #{$vehicleId}";
            $label = $vehicle ? trim("{$regNo} · {$vehicle->make} {$vehicle->model}") : $regNo;
            $isReplacement = (bool) $vCharts->first()->is_replacement;

            $vKm = '0.000000';
            $vNormalOt = 0;
            $vDoubleOt = 0;
            $vNightOuts = 0;
            foreach ($vCharts as $vc) {
                $vKm = $this->math->add($vKm, (string) $vc->commercial_km, 6);
                $vNormalOt += (int) $vc->normal_ot_minutes;
                $vDoubleOt += (int) $vc->double_ot_minutes;
                $vNightOuts += (int) $vc->night_outs;
            }

            $custodyDays = $cycleDays;
            if ($chartsByVehicle->count() > 1) {
                $firstStart = $vCharts->min('starts_at');
                $lastEnd = $vCharts->max('ends_at');
                if ($firstStart && $lastEnd) {
                    $startDay = CarbonImmutable::parse($firstStart)->startOfDay();
                    $endDay = CarbonImmutable::parse($lastEnd)->endOfDay();
                    $custodyDays = max(1, min($cycleDays, $startDay->diffInDays($endDay) + 1));
                }
            }

            $vBaseRent = $chartsByVehicle->count() > 1
                ? $this->math->div($this->math->mul((string) $cycle->base_rent_amount, (string) $custodyDays, 6), (string) $cycleDays, 6)
                : (string) $cycle->base_rent_amount;

            $vNormalOtHours = $this->math->div((string) $vNormalOt, '60', 6);
            $vDoubleOtHours = $this->math->div((string) $vDoubleOt, '60', 6);

            $terms = $agreement?->terms ?? [];
            $normalOtRate = (string) ($terms['normal_ot_rate'] ?? '1500.000000');
            $doubleOtRate = (string) ($terms['double_ot_rate'] ?? '2000.000000');
            $nightOutRate = (string) ($terms['night_out_rate'] ?? '1000.000000');

            $vNormalOtAmt = $this->math->mul($vNormalOtHours, $normalOtRate, 6);
            $vDoubleOtAmt = $this->math->mul($vDoubleOtHours, $doubleOtRate, 6);
            $vOtAmt = $this->math->add($vNormalOtAmt, $vDoubleOtAmt, 6);
            $vNightOutAmt = $this->math->mul((string) $vNightOuts, $nightOutRate, 6);

            $vSubtotal = $this->math->add($vBaseRent, $vOtAmt, 6);
            $vSubtotal = $this->math->add($vSubtotal, $vNightOutAmt, 6);

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
                'normal_ot_hours' => $vNormalOtHours,
                'double_ot_hours' => $vDoubleOtHours,
                'night_outs' => $vNightOuts,
                'allocated_base_rent' => $vBaseRent,
                'allocated_ot_amount' => $vOtAmt,
                'allocated_night_out_amount' => $vNightOutAmt,
                'subtotal' => $vSubtotal,
            ];
        }

        // 2. Driver Attributions
        $driverAttributions = [];
        $chartsByDriver = $cycle->charts->groupBy('driver_name_snapshot');
        foreach ($chartsByDriver as $driverName => $dCharts) {
            $dKm = '0.000000';
            $dNormalOt = 0;
            $dDoubleOt = 0;
            $dNightOuts = 0;
            $driverRef = '';

            foreach ($dCharts as $dc) {
                $dKm = $this->math->add($dKm, (string) $dc->commercial_km, 6);
                $dNormalOt += (int) $dc->normal_ot_minutes;
                $dDoubleOt += (int) $dc->double_ot_minutes;
                $dNightOuts += (int) $dc->night_outs;
                if ($dc->runningChart?->driver_reference_snapshot) {
                    $driverRef = $dc->runningChart->driver_reference_snapshot;
                }
            }

            $terms = $agreement?->terms ?? [];
            $normalOtRate = (string) ($terms['normal_ot_rate'] ?? '1500.000000');
            $doubleOtRate = (string) ($terms['double_ot_rate'] ?? '2000.000000');
            $nightOutRate = (string) ($terms['night_out_rate'] ?? '1000.000000');

            $dNormalOtHours = $this->math->div((string) $dNormalOt, '60', 6);
            $dNormalOtAmt = $this->math->mul($dNormalOtHours, $normalOtRate, 6);

            $dDoubleOtHours = $this->math->div((string) $dDoubleOt, '60', 6);
            $dDoubleOtAmt = $this->math->mul($dDoubleOtHours, $doubleOtRate, 6);

            $dOtAmt = $this->math->add($dNormalOtAmt, $dDoubleOtAmt, 6);
            $dNightOutAmt = $this->math->mul((string) $dNightOuts, $nightOutRate, 6);
            $dEarnings = $this->math->add($dOtAmt, $dNightOutAmt, 6);

            $driverAttributions[] = [
                'driver_name' => (string) $driverName,
                'driver_reference' => $driverRef,
                'charts_count' => $dCharts->count(),
                'total_km' => $dKm,
                'normal_ot_hours' => $dNormalOtHours,
                'normal_ot_amount' => $dNormalOtAmt,
                'double_ot_hours' => $dDoubleOtHours,
                'double_ot_amount' => $dDoubleOtAmt,
                'night_outs' => $dNightOuts,
                'night_out_amount' => $dNightOutAmt,
                'total_earnings' => $dEarnings,
            ];
        }

        // 3. Line Items
        $lineItems = $cycle->items->map(fn (BillingCycleItem $item) => [
            'id' => $item->id,
            'item_type' => $item->item_type->value,
            'category' => $item->category->value,
            'description' => $item->description,
            'quantity' => (string) $item->quantity,
            'unit_rate' => (string) $item->unit_rate,
            'amount' => (string) $item->amount,
        ])->all();

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
                // Mode A: Supplementary usage invoice for the delta
                $deltaAmount = $this->math->sub((string) $cycle->net_subtotal, (string) $cycle->base_rent_amount, 6);
                if ($this->math->compare($deltaAmount, '0.000000') > 0) {
                    $suppInvoice = $this->issueSupplementaryInvoice($kind, $context, $agreement, $cycle, $deltaAmount, $data);
                    $cycle->supplementary_invoice_id = $suppInvoice->id;
                }
            } else {
                // Mode B: Consolidated revision
                $finalInvoice = $this->issueConsolidatedInvoice($kind, $context, $agreement, $cycle, (string) $cycle->net_subtotal, $data);
                $cycle->final_invoice_id = $finalInvoice->id;

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
        $description = 'Base Rental · '.$agreement->reference.' · '.$cycle->period_from?->format('Y-m-d').' To '.$cycle->period_until?->format('Y-m-d');

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
            lines: [
                new InvoiceLineData(
                    lineNumber: 1,
                    description: $description,
                    quantity: self::WHOLE_CHARGE,
                    unitPrice: $amount,
                    lineType: InvoiceLineType::Charge,
                    sourceLineType: $sourceType,
                    sourceLineId: $sourceId,
                ),
            ],
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
            sourceLines: [
                new InvoiceSourceLineData(
                    tenantId: $context->tenantId,
                    organizationUnitId: $context->organizationUnitId,
                    sourceType: $sourceType,
                    sourceId: $sourceId,
                    sourceLineType: $sourceType,
                    sourceLineId: $sourceId,
                    sourceQuantity: self::WHOLE_CHARGE,
                    invoicedQuantity: self::WHOLE_CHARGE,
                    sourceUnitPrice: $amount,
                    sourceLineTotal: $amount,
                    invoicedLineTotal: $amount,
                ),
            ],
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
        $description = 'Supplementary Usage · '.$agreement->reference.' · Excess KM, OT & Charges ('.$cycle->period_from?->format('Y-m-d').' To '.$cycle->period_until?->format('Y-m-d').')';

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
            lines: [
                new InvoiceLineData(
                    lineNumber: 1,
                    description: $description,
                    quantity: self::WHOLE_CHARGE,
                    unitPrice: $deltaAmount,
                    lineType: InvoiceLineType::Charge,
                    sourceLineType: $sourceType,
                    sourceLineId: $sourceId,
                ),
            ],
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
            sourceLines: [
                new InvoiceSourceLineData(
                    tenantId: $context->tenantId,
                    organizationUnitId: $context->organizationUnitId,
                    sourceType: $sourceType,
                    sourceId: $sourceId,
                    sourceLineType: $sourceType,
                    sourceLineId: $sourceId,
                    sourceQuantity: self::WHOLE_CHARGE,
                    invoicedQuantity: self::WHOLE_CHARGE,
                    sourceUnitPrice: $deltaAmount,
                    sourceLineTotal: $deltaAmount,
                    invoicedLineTotal: $deltaAmount,
                ),
            ],
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
        $description = 'Consolidated Rental & Usage · '.$agreement->reference.' · ('.$cycle->period_from?->format('Y-m-d').' To '.$cycle->period_until?->format('Y-m-d').')';

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
            lines: [
                new InvoiceLineData(
                    lineNumber: 1,
                    description: $description,
                    quantity: self::WHOLE_CHARGE,
                    unitPrice: $totalAmount,
                    lineType: InvoiceLineType::Charge,
                    sourceLineType: $sourceType,
                    sourceLineId: $sourceId,
                ),
            ],
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
            sourceLines: [
                new InvoiceSourceLineData(
                    tenantId: $context->tenantId,
                    organizationUnitId: $context->organizationUnitId,
                    sourceType: $sourceType,
                    sourceId: $sourceId,
                    sourceLineType: $sourceType,
                    sourceLineId: $sourceId,
                    sourceQuantity: self::WHOLE_CHARGE,
                    invoicedQuantity: self::WHOLE_CHARGE,
                    sourceUnitPrice: $totalAmount,
                    sourceLineTotal: $totalAmount,
                    invoicedLineTotal: $totalAmount,
                ),
            ],
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

        if ($initialAllocations->isNotEmpty()) {
            $this->invoiceStatusService->transition($finalInvoice, InvoiceStatus::Approved, $context->actorId, 'Auto-approved for payment reallocation');
            $finalInvoice = $this->invoiceStatusService->transition($finalInvoice, InvoiceStatus::Posted, $context->actorId, 'Auto-posted for payment reallocation');
        }

        $transferredPaymentTotal = '0.000000';

        foreach ($initialAllocations as $alloc) {
            $payment = $alloc->payment;
            if ($payment) {
                $reversalDate = now()->toDateString();
                if ($alloc->allocation_date && $reversalDate < $alloc->allocation_date->toDateString()) {
                    $reversalDate = $alloc->allocation_date->toDateString();
                }

                // 1. Reverse the allocation from initial invoice
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

                // 2. Allocate payment onto the new consolidated invoice
                $this->allocationService->allocate(
                    $payment,
                    [
                        new PaymentAllocationData(
                            invoiceId: $finalInvoice->id,
                            allocatedAmount: (string) $alloc->allocated_amount,
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

                $transferredPaymentTotal = $this->math->add($transferredPaymentTotal, (string) $alloc->allocated_amount, 6);
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

        if ($initialInvoice->status === InvoiceStatus::Draft) {
            try {
                $this->invoiceStatusService->transition(
                    $initialInvoice,
                    InvoiceStatus::Void,
                    $context->actorId,
                    'Provisional invoice superseded by consolidated revision invoice '.$finalInvoice->invoice_number
                );
            } catch (\Throwable) {
            }
        }
    }

    private function assertCycleVersion(BillingCycle $cycle, int $expectedVersion): void
    {
        if ((int) $cycle->row_version !== $expectedVersion) {
            throw new ConflictHttpException("Cycle version mismatch: expected {$expectedVersion}, currently {$cycle->row_version}.");
        }
    }
}
