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
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\Invoice\Services\TaxedSourceInvoiceFactory;
use Modules\Payment\Services\PaymentAllocationService;
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
use Modules\VehicleRental\Models\AgreementAdjustment;
use Modules\VehicleRental\Models\AgreementAdjustmentHistory;
use Modules\VehicleRental\Models\BillingCycle;
use Modules\VehicleRental\Models\BillingCycleChart;
use Modules\VehicleRental\Models\BillingCycleItem;
use Modules\VehicleRental\Models\CustomerAgreement;
use Modules\VehicleRental\Models\OwnerAgreement;
use Modules\VehicleRental\Models\RunningChart;
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
        private readonly PaymentAllocationService $allocationService,
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

            $billingMode = isset($input['billing_mode'])
                ? BillingCycleMode::from((string) $input['billing_mode'])
                : BillingCycleMode::WithoutRunningChart;

            $finalizationMode = isset($input['finalization_mode'])
                ? BillingFinalizationMode::from((string) $input['finalization_mode'])
                : BillingFinalizationMode::SupplementaryInvoice;

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

    public function attachRunningCharts(
        AgreementContext $context,
        int $cycleId,
        array $chartIds,
        int $expectedVersion
    ): BillingCycle {
        $this->authorization->assertBilling($context, AgreementKind::Customer, true);

        return DB::transaction(function () use ($context, $cycleId, $chartIds, $expectedVersion): BillingCycle {
            $cycle = BillingCycle::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->lockForUpdate()
                ->findOrFail($cycleId);

            $this->assertCycleVersion($cycle, $expectedVersion);

            if ($cycle->status === BillingCycleStatus::Finalized || $cycle->status === BillingCycleStatus::Cancelled) {
                throw ValidationException::withMessages(['cycle' => ['Cannot attach running charts to a finalized or cancelled cycle.']]);
            }

            $agreement = $cycle->customerAgreement ?? $cycle->ownerAgreement;
            $terms = $agreement?->terms ?? [];

            // Existing chart attachments
            $cycle->charts()->delete();

            $charts = RunningChart::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->whereIn('id', $chartIds)
                ->with(['vehicleUse.vehicle'])
                ->get();

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
                $chartAttachment->driver_name_snapshot = $chart->driver_observation ?? 'Assigned Driver';
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
        $this->authorization->assertBilling($context, AgreementKind::Customer, true);

        return DB::transaction(function () use ($context, $cycleId, $data, $expectedVersion): BillingCycleItem {
            $cycle = BillingCycle::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->lockForUpdate()
                ->findOrFail($cycleId);

            $this->assertCycleVersion($cycle, $expectedVersion);

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
        $this->authorization->assertBilling($context, AgreementKind::Customer, true);

        DB::transaction(function () use ($context, $cycleId, $itemId, $expectedVersion): void {
            $cycle = BillingCycle::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->lockForUpdate()
                ->findOrFail($cycleId);

            $this->assertCycleVersion($cycle, $expectedVersion);

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
        $this->authorization->assertBilling($context, AgreementKind::Customer, false);

        $cycle = BillingCycle::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->with(['charts.runningChart', 'charts.vehicle', 'items', 'initialInvoice'])
            ->findOrFail($cycleId);

        $initialAmount = $cycle->initialInvoice?->grand_total ?? '0.000000';
        $grossDelta = $this->math->sub((string) $cycle->net_subtotal, (string) $cycle->base_rent_amount, 6);

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
        ];
    }

    public function finalize(
        AgreementContext $context,
        int $cycleId,
        array $data,
        int $expectedVersion
    ): BillingCycle {
        $this->authorization->assertBilling($context, AgreementKind::Customer, true);

        return DB::transaction(function () use ($context, $cycleId, $data, $expectedVersion): BillingCycle {
            $cycle = BillingCycle::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->lockForUpdate()
                ->findOrFail($cycleId);

            $this->assertCycleVersion($cycle, $expectedVersion);

            if ($cycle->status === BillingCycleStatus::Finalized) {
                throw ValidationException::withMessages(['cycle' => ['Billing cycle is already finalized.']]);
            }

            $finalizationMode = isset($data['finalization_mode'])
                ? BillingFinalizationMode::from((string) $data['finalization_mode'])
                : $cycle->finalization_mode;

            $agreement = $cycle->customerAgreement ?? $cycle->ownerAgreement;
            $kind = $cycle->customer_agreement_id !== null ? AgreementKind::Customer : AgreementKind::Owner;

            if ($finalizationMode === BillingFinalizationMode::SupplementaryInvoice) {
                // Mode A: Supplementary usage invoice for the delta
                $deltaAmount = $this->math->sub((string) $cycle->net_subtotal, (string) $cycle->base_rent_amount, 6);
                if ($this->math->compare($deltaAmount, '0.000000') > 0) {
                    $suppInvoice = $this->issueSupplementaryInvoice($kind, $context, $agreement, $cycle, $deltaAmount, $data);
                    $cycle->supplementary_invoice_id = $suppInvoice->id;
                }
            } else {
                // Mode B: Consolidated revision
                if ($cycle->initial_invoice_id !== null) {
                    $this->voidInitialInvoiceAndReallocate($context, $agreement, $cycle);
                }

                $finalInvoice = $this->issueConsolidatedInvoice($kind, $context, $agreement, $cycle, (string) $cycle->net_subtotal, $data);
                $cycle->final_invoice_id = $finalInvoice->id;
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
            invoiceType: InvoiceType::Rental,
            direction: $direction,
            invoiceDate: $invoiceDate,
            dueDate: $dueDate,
            currencyId: $agreement->currency_id,
            exchangeRate: $exchangeRate,
            partyType: $partyType,
            partyId: $partyId,
            createdBy: $context->actorId,
            notes: 'Stage 1 Provisional Rental Invoice',
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
                    sourceDocumentNumber: $cycle->cycle_number,
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
            invoiceType: InvoiceType::Rental,
            direction: $direction,
            invoiceDate: $invoiceDate,
            dueDate: $dueDate,
            currencyId: $agreement->currency_id,
            exchangeRate: (string) $cycle->exchange_rate,
            partyType: $partyType,
            partyId: $partyId,
            createdBy: $context->actorId,
            notes: 'Stage 2 Supplementary Usage Invoice',
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
                    sourceDocumentNumber: $cycle->cycle_number.'-SUPP',
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
            invoiceType: InvoiceType::Rental,
            direction: $direction,
            invoiceDate: $invoiceDate,
            dueDate: $dueDate,
            currencyId: $agreement->currency_id,
            exchangeRate: (string) $cycle->exchange_rate,
            partyType: $partyType,
            partyId: $partyId,
            createdBy: $context->actorId,
            notes: 'Stage 2 Consolidated Revised Rental Invoice',
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
                    sourceDocumentNumber: $cycle->cycle_number.'-CONSOL',
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
        BillingCycle $cycle
    ): void {
        $initialInvoice = Invoice::query()
            ->forTenant($context->tenantId)
            ->findOrFail($cycle->initial_invoice_id);

        // Record Credit Note adjustment in agreement adjustments
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
        $adjustment->reason = 'Voided for consolidated invoice revision cycle: '.$cycle->cycle_number;
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
    }

    private function assertCycleVersion(BillingCycle $cycle, int $expectedVersion): void
    {
        if ((int) $cycle->row_version !== $expectedVersion) {
            throw new ConflictHttpException("Cycle version mismatch: expected {$expectedVersion}, currently {$cycle->row_version}.");
        }
    }
}
