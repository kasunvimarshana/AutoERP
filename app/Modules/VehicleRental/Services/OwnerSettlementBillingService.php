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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\Enums\FinanceAccountRoleCode;
use Modules\Finance\Enums\FinancePostingProfileCode;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceAdjustmentData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\DTOs\InvoicePostingLineData;
use Modules\Invoice\DTOs\InvoicePostingPlanData;
use Modules\Invoice\DTOs\InvoiceSourceData;
use Modules\Invoice\DTOs\InvoiceSourceLineData;
use Modules\Invoice\Enums\AdjustmentEffect;
use Modules\Invoice\Enums\AdjustmentType;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceLineType;
use Modules\Invoice\Enums\InvoicePartyType;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\Invoice\Services\TaxedSourceInvoiceFactory;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AgreementAdjustmentStatus;
use Modules\VehicleRental\Enums\AgreementAdjustmentType;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\BillingCycleStatus;
use Modules\VehicleRental\Enums\RentalExpenseAllocationStatus;
use Modules\VehicleRental\Models\AgreementAdjustment;
use Modules\VehicleRental\Models\AgreementAdjustmentHistory;
use Modules\VehicleRental\Models\BillingCycle;
use Modules\VehicleRental\Models\OwnerAgreement;
use Modules\VehicleRental\Models\OwnerBaseCharge;
use Modules\VehicleRental\Models\OwnerUsageCharge;
use Modules\VehicleRental\Models\RentalExpenseAllocation;

final class OwnerSettlementBillingService
{
    private const WHOLE_CHARGE = '1.000000';

    public function __construct(
        private readonly DecimalMath $math,
        private readonly RentalAuthorization $authorization,
        private readonly TaxedSourceInvoiceFactory $invoiceFactory,
        private readonly InvoiceCreationService $invoiceService,
    ) {}

    public function generateOwnerSettlementBill(
        AgreementContext $context,
        int $ownerAgreementId,
        array $input
    ): Invoice {
        $this->authorization->assertBilling($context, AgreementKind::Owner, true);

        $startDate = (string) ($input['start_date'] ?? '');
        $endDate = (string) ($input['end_date'] ?? '');
        if ($startDate === '' || $endDate === '') {
            throw ValidationException::withMessages(['dates' => ['Specify both start date and end date for the owner settlement bill.']]);
        }

        $start = CarbonImmutable::parse($startDate)->startOfDay();
        $end = CarbonImmutable::parse($endDate)->endOfDay();
        if ($end->lessThan($start)) {
            throw ValidationException::withMessages(['end_date' => ['End date must be on or after start date.']]);
        }

        return DB::transaction(function () use ($context, $ownerAgreementId, $input, $startDate, $endDate, $start, $end): Invoice {
            $agreement = OwnerAgreement::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->with(['party', 'vehicle'])
                ->lockForUpdate()
                ->findOrFail($ownerAgreementId);

            // Guard against duplicate overlapping owner settlements
            $existingSettlements = Invoice::query()
                ->where('tenant_id', $context->tenantId)
                ->where('organization_unit_id', $context->organizationUnitId)
                ->whereNotIn('status', [InvoiceStatus::Cancelled->value, InvoiceStatus::Void->value, InvoiceStatus::Reversed->value])
                ->whereHas('sources', fn ($sq) => $sq
                    ->where('source_type', 'vehicle_rental_owner_settlement')
                    ->where('source_id', $agreement->id)
                )
                ->with(['sources', 'lines'])
                ->get();

            $targetSettleKey = $agreement->reference.'-SETTLE-'.CarbonImmutable::parse($startDate)->format('Ym');
            foreach ($existingSettlements as $existingInv) {
                foreach ($existingInv->sources as $src) {
                    if ($src->source_document_number === $targetSettleKey) {
                        throw ValidationException::withMessages(['settlement' => ['An active owner settlement bill already exists for this settlement month ('.$existingInv->invoice_number.').']]);
                    }
                }
                foreach ($existingInv->lines as $line) {
                    if (preg_match('/(\d{4}-\d{2}-\d{2}|\d{2}[\/\-]\d{2}[\/\-]\d{4})\s*(?:To|-)\s*(\d{4}-\d{2}-\d{2}|\d{2}[\/\-]\d{2}[\/\-]\d{4})/i', $line->description ?? '', $m)) {
                        $parseDate = static function (string $d): CarbonImmutable {
                            if (str_contains($d, '/')) {
                                return CarbonImmutable::createFromFormat('d/m/Y', $d);
                            }
                            if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $d)) {
                                return CarbonImmutable::createFromFormat('d-m-Y', $d);
                            }

                            return CarbonImmutable::parse($d);
                        };
                        $exStart = $parseDate($m[1])->startOfDay();
                        $exEnd = $parseDate($m[2])->endOfDay();
                        if ($exStart->lessThanOrEqualTo($end) && $exEnd->greaterThanOrEqualTo($start)) {
                            throw ValidationException::withMessages(['settlement' => ['An active owner settlement bill already covers an overlapping period ('.$m[1].' to '.$m[2].' on invoice '.$existingInv->invoice_number.').']]);
                        }
                    }
                }
            }

            $whtRate = isset($input['wht_rate']) && is_numeric($input['wht_rate'])
                ? (string) $input['wht_rate']
                : '0.000000';

            // 1. Fetch base charges for period
            $baseCharges = OwnerBaseCharge::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->where('agreement_id', $agreement->id)
                ->whereNull('voided_at')
                ->where('period_from', '<=', $endDate)
                ->where('period_until', '>=', $startDate)
                ->get();

            $basePayable = '0.000000';
            foreach ($baseCharges as $charge) {
                $basePayable = $this->math->add($basePayable, (string) $charge->amount, 6);
            }

            // 2. Fetch usage charges for period (OT, mileage)
            $usageCharges = OwnerUsageCharge::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->where('agreement_id', $agreement->id)
                ->whereNull('voided_at')
                ->where('period_from', '<=', $endDate)
                ->where('period_until', '>=', $startDate)
                ->get();

            $usagePayable = '0.000000';
            foreach ($usageCharges as $charge) {
                $usagePayable = $this->math->add($usagePayable, (string) $charge->amount, 6);
            }

            // Also check unbilled owner BillingCycles if no owner base charges exist
            $unbilledOwnerCycles = collect();
            if ($baseCharges->isEmpty()) {
                $unbilledOwnerCycles = BillingCycle::query()
                    ->forContext($context->tenantId, $context->organizationUnitId)
                    ->where('owner_agreement_id', $agreement->id)
                    ->where('period_from', '<=', $endDate)
                    ->where('period_until', '>=', $startDate)
                    ->whereNull('initial_invoice_id')
                    ->whereNull('final_invoice_id')
                    ->whereNull('supplementary_invoice_id')
                    ->get();

                foreach ($unbilledOwnerCycles as $oCycle) {
                    $basePayable = $this->math->add($basePayable, (string) $oCycle->base_rent_amount, 6);
                    $cycleUsage = $this->math->add((string) $oCycle->excess_km_amount, (string) $oCycle->overtime_amount, 6);
                    $cycleUsage = $this->math->add($cycleUsage, (string) $oCycle->night_out_amount, 6);
                    $cycleUsage = $this->math->add($cycleUsage, (string) $oCycle->tolls_amount, 6);
                    $cycleUsage = $this->math->add($cycleUsage, (string) $oCycle->additions_amount, 6);
                    $usagePayable = $this->math->add($usagePayable, $cycleUsage, 6);
                }
            }

            // Defect 5: Query approved AgreementAdjustment records for the owner agreement
            $agreementAdjustments = AgreementAdjustment::query()
                ->where('tenant_id', $context->tenantId)
                ->where('organization_unit_id', $context->organizationUnitId)
                ->where('owner_agreement_id', $agreement->id)
                ->where('status', AgreementAdjustmentStatus::Authorized->value)
                ->whereNull('invoice_id')
                ->get();

            $adjustmentAdditions = '0.000000';
            $adjustmentDeductions = '0.000000';
            foreach ($agreementAdjustments as $adj) {
                if ($adj->adjustment_type === AgreementAdjustmentType::DebitNote) {
                    $adjustmentAdditions = $this->math->add($adjustmentAdditions, (string) $adj->amount, 6);
                } else {
                    $adjustmentDeductions = $this->math->add($adjustmentDeductions, (string) $adj->amount, 6);
                }
            }

            $grossPayable = $this->math->add($basePayable, $usagePayable, 6);
            $grossPayable = $this->math->add($grossPayable, $adjustmentAdditions, 6);
            if ($this->math->compare($grossPayable, '0.000000') <= 0) {
                throw ValidationException::withMessages(['settlement' => ['No payable rental charges found for the specified settlement period.']]);
            }

            // 3. Calculate 10% Withholding Tax
            $whtAmount = $this->math->div($this->math->mul($grossPayable, $whtRate, 6), '100.000000', 6);

            // 4. Fetch maintenance expense chargebacks
            $expenseAllocations = RentalExpenseAllocation::query()
                ->where('tenant_id', $context->tenantId)
                ->where('target_owner_agreement_id', $agreement->id)
                ->where('status', RentalExpenseAllocationStatus::Confirmed->value)
                ->whereNull('invoice_id')
                ->get();

            $maintenanceDeductions = '0.000000';
            foreach ($expenseAllocations as $alloc) {
                $maintenanceDeductions = $this->math->add($maintenanceDeductions, (string) $alloc->total_amount, 6);
            }

            // Defect 12: Capping - ensure non-tax deductions do not exceed max(0, grossPayable - whtAmount)
            $maxDeductionsCapacity = $this->math->compare($grossPayable, $whtAmount) > 0
                ? $this->math->sub($grossPayable, $whtAmount, 6)
                : '0.000000';

            $applicableMaintenance = $this->math->compare($maintenanceDeductions, $maxDeductionsCapacity) > 0
                ? $maxDeductionsCapacity
                : $maintenanceDeductions;

            $remainingCapacityAfterMaint = $this->math->sub($maxDeductionsCapacity, $applicableMaintenance, 6);
            $applicableAdjDeductions = $this->math->compare($adjustmentDeductions, $remainingCapacityAfterMaint) > 0
                ? $remainingCapacityAfterMaint
                : $adjustmentDeductions;

            $totalDeductions = $this->math->add($whtAmount, $applicableMaintenance, 6);
            $totalDeductions = $this->math->add($totalDeductions, $applicableAdjDeductions, 6);

            $netPayable = $this->math->sub($grossPayable, $totalDeductions, 6);
            if ($this->math->compare($netPayable, '0.000000') < 0) {
                $netPayable = '0.000000';
            }

            $invoiceDate = (string) ($input['invoice_date'] ?? now()->toDateString());
            $dueDate = ! empty($input['due_date']) ? (string) $input['due_date'] : null;
            if ($dueDate !== null && $dueDate < $invoiceDate) {
                throw ValidationException::withMessages(['due_date' => ['Due date must be on or after invoice date.']]);
            }
            $exchangeRate = (string) ($input['exchange_rate'] ?? '1.000000');

            $sourceType = 'vehicle_rental_owner_settlement';
            $sourceId = (int) $agreement->id;
            $vehicleNo = $agreement->vehicle?->registration_number ?? $agreement->vehicle?->vehicle_number ?? 'Fleet Vehicle';
            $description = 'Lessor Settlement · '.$agreement->reference.' · '.$vehicleNo.' ('.$start->format('d/m/Y').' To '.$end->format('d/m/Y').')';

            $notes = 'Owner Settlement AP Bill. Gross: '.$grossPayable.', WHT: '.$whtAmount.', Deductions: '.$applicableMaintenance.', Net Payable: '.$netPayable;

            $hasWht = $this->math->compare($whtAmount, '0.000000') > 0;
            $hasMaintenance = $this->math->compare($applicableMaintenance, '0.000000') > 0;
            $hasAdjDeductions = $this->math->compare($applicableAdjDeductions, '0.000000') > 0;

            $lines = [];
            $sourceLines = [];
            $lineNum = 1;

            if ($baseCharges->isNotEmpty()) {
                foreach ($baseCharges as $charge) {
                    $srcLineId = ($sourceId * 1000) + $lineNum;
                    $desc = 'Lessor Base Lease Rent · Charge #'.$charge->id.' ('.$charge->period_from.' To '.$charge->period_until.')';
                    $lines[] = new InvoiceLineData(
                        lineNumber: $lineNum,
                        description: $desc,
                        quantity: self::WHOLE_CHARGE,
                        unitPrice: (string) $charge->amount,
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
                        sourceUnitPrice: (string) $charge->amount,
                        sourceLineTotal: (string) $charge->amount,
                        invoicedLineTotal: (string) $charge->amount,
                    );
                    $lineNum++;
                }
            } elseif ($this->math->compare($basePayable, '0.000000') > 0) {
                $srcLineId = ($sourceId * 1000) + $lineNum;
                $desc = 'Gross Vehicle Lease Rent · '.$agreement->reference.' · '.$vehicleNo.' ('.$start->format('d/m/Y').' To '.$end->format('d/m/Y').')';
                $lines[] = new InvoiceLineData(
                    lineNumber: $lineNum,
                    description: $desc,
                    quantity: self::WHOLE_CHARGE,
                    unitPrice: $basePayable,
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
                    sourceUnitPrice: $basePayable,
                    sourceLineTotal: $basePayable,
                    invoicedLineTotal: $basePayable,
                );
                $lineNum++;
            }

            if ($usageCharges->isNotEmpty()) {
                foreach ($usageCharges as $charge) {
                    $srcLineId = ($sourceId * 1000) + $lineNum;
                    $desc = 'Lessor Operational Usage & Overtime · Charge #'.$charge->id.' ('.$charge->period_from.' To '.$charge->period_until.')';
                    $lines[] = new InvoiceLineData(
                        lineNumber: $lineNum,
                        description: $desc,
                        quantity: self::WHOLE_CHARGE,
                        unitPrice: (string) $charge->amount,
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
                        sourceUnitPrice: (string) $charge->amount,
                        sourceLineTotal: (string) $charge->amount,
                        invoicedLineTotal: (string) $charge->amount,
                    );
                    $lineNum++;
                }
            } elseif ($this->math->compare($usagePayable, '0.000000') > 0) {
                $srcLineId = ($sourceId * 1000) + $lineNum;
                $desc = 'Lessor Operational Usage Charges ('.$start->format('d/m/Y').' To '.$end->format('d/m/Y').')';
                $lines[] = new InvoiceLineData(
                    lineNumber: $lineNum,
                    description: $desc,
                    quantity: self::WHOLE_CHARGE,
                    unitPrice: $usagePayable,
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
                    sourceUnitPrice: $usagePayable,
                    sourceLineTotal: $usagePayable,
                    invoicedLineTotal: $usagePayable,
                );
                $lineNum++;
            }

            foreach ($agreementAdjustments->where('adjustment_type', AgreementAdjustmentType::DebitNote) as $adj) {
                $srcLineId = ($sourceId * 1000) + $lineNum;
                $desc = 'Lessor Addition · '.$adj->category->value.' ('.$adj->reason.')';
                $lines[] = new InvoiceLineData(
                    lineNumber: $lineNum,
                    description: $desc,
                    quantity: self::WHOLE_CHARGE,
                    unitPrice: (string) $adj->amount,
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
                    sourceUnitPrice: (string) $adj->amount,
                    sourceLineTotal: (string) $adj->amount,
                    invoicedLineTotal: (string) $adj->amount,
                );
                $lineNum++;
            }

            $adjustments = [];
            if ($hasWht) {
                $adjustments[] = new InvoiceAdjustmentData(
                    name: 'Statutory Withholding Tax Deduction ('.rtrim(rtrim($whtRate, '0'), '.').'%)',
                    adjustmentType: AdjustmentType::Withholding,
                    effect: AdjustmentEffect::Decrease,
                    amount: $whtAmount,
                    isSystemGenerated: true,
                );
            }

            if ($hasMaintenance) {
                $adjustments[] = new InvoiceAdjustmentData(
                    name: 'Fleet Maintenance Recovery',
                    adjustmentType: AdjustmentType::Discount,
                    effect: AdjustmentEffect::Decrease,
                    amount: $applicableMaintenance,
                    isSystemGenerated: true,
                );
            }

            if ($hasAdjDeductions) {
                $adjustments[] = new InvoiceAdjustmentData(
                    name: 'Contractual Agreement Deductions',
                    adjustmentType: AdjustmentType::Discount,
                    effect: AdjustmentEffect::Decrease,
                    amount: $applicableAdjDeductions,
                    isSystemGenerated: true,
                );
            }

            $postingLines = [
                new InvoicePostingLineData(
                    role: FinanceAccountRoleCode::RentalExpense,
                    debit: $grossPayable,
                    description: 'Gross vehicle lease rent: '.$agreement->reference,
                ),
            ];

            if ($hasWht) {
                $postingLines[] = new InvoicePostingLineData(
                    role: FinanceAccountRoleCode::WithholdingPayable,
                    credit: $whtAmount,
                    description: 'Statutory withholding tax payable',
                );
            }

            if ($hasMaintenance) {
                $postingLines[] = new InvoicePostingLineData(
                    role: FinanceAccountRoleCode::RentalExpense,
                    credit: $applicableMaintenance,
                    description: 'Fleet maintenance chargeback recovery',
                );
            }

            if ($hasAdjDeductions) {
                $postingLines[] = new InvoicePostingLineData(
                    role: FinanceAccountRoleCode::RentalExpense,
                    credit: $applicableAdjDeductions,
                    description: 'Contractual agreement deductions recovery',
                );
            }

            if ($this->math->compare($netPayable, '0.000000') > 0) {
                $postingLines[] = new InvoicePostingLineData(
                    role: FinanceAccountRoleCode::Payable,
                    credit: $netPayable,
                    description: 'Net payable to lessor: '.$agreement->reference,
                );
            }

            $postingPlan = new InvoicePostingPlanData(
                profile: FinancePostingProfileCode::SupplierRentalInvoice,
                postingDate: $invoiceDate,
                lines: $postingLines,
                description: $description,
            );

            $referenceFields = [
                ['label' => 'Agreement Reference', 'value' => (string) $agreement->reference],
                ['label' => 'Vehicle Registration', 'value' => (string) $vehicleNo],
                ['label' => 'Settlement Period', 'value' => $start->format('d/m/Y').' To '.$end->format('d/m/Y')],
                ['label' => 'Gross Lease Payable', 'value' => (string) $grossPayable],
            ];
            if ($hasWht) {
                $referenceFields[] = ['label' => 'Statutory WHT ('.rtrim(rtrim($whtRate, '0'), '.').'%)', 'value' => '-'.(string) $whtAmount];
            }
            if ($hasMaintenance) {
                $referenceFields[] = ['label' => 'Maintenance Chargeback', 'value' => '-'.(string) $applicableMaintenance];
            }
            if ($hasAdjDeductions) {
                $referenceFields[] = ['label' => 'Agreement Deductions', 'value' => '-'.(string) $applicableAdjDeductions];
            }
            $referenceFields[] = ['label' => 'Net Settlement Payable', 'value' => (string) $netPayable];

            $source = new CreateInvoiceData(
                tenantId: $context->tenantId,
                organizationUnitId: $context->organizationUnitId,
                invoiceType: InvoiceType::Purchase,
                direction: InvoiceDirection::Inbound,
                invoiceDate: $invoiceDate,
                dueDate: $dueDate,
                currencyId: $agreement->currency_id,
                exchangeRate: $exchangeRate,
                partyType: InvoicePartyType::Supplier->value,
                partyId: $agreement->supplier_id,
                createdBy: $context->actorId,
                notes: $notes,
                supplyPeriodStart: $start->toDateString(),
                supplyPeriodEnd: $end->toDateString(),
                lines: $lines,
                adjustments: $adjustments,
                sources: [
                    new InvoiceSourceData(
                        tenantId: $context->tenantId,
                        organizationUnitId: $context->organizationUnitId,
                        sourceType: $sourceType,
                        sourceId: $sourceId,
                        sourceDocumentNumber: $agreement->reference.'-SETTLE-'.CarbonImmutable::parse($startDate)->format('Ym'),
                        sourceDocumentDate: $invoiceDate,
                        sourceSubtotal: $grossPayable,
                        sourceGrandTotal: $netPayable,
                    ),
                ],
                sourceLines: $sourceLines,
                purchaserReferenceFields: $referenceFields,
                postingPlan: $postingPlan,
            );

            $invoice = $this->invoiceService->create($source);

            // Defect 2: Mark unbilled owner cycles as finalized
            foreach ($unbilledOwnerCycles as $cycle) {
                $cycle->final_invoice_id = $invoice->id;
                $cycle->status = BillingCycleStatus::Finalized;
                $cycle->finalized_at = now();
                $cycle->finalized_by = $context->actorId;
                $cycle->row_version += 1;
                $cycle->save();
            }

            // Link expense allocations to this invoice
            foreach ($expenseAllocations as $alloc) {
                $alloc->invoice_id = $invoice->id;
                $alloc->status = RentalExpenseAllocationStatus::Invoiced;
                $alloc->row_version += 1;
                $alloc->save();
            }

            // Defect 5: Link agreement adjustments to this invoice and transition to Applied
            foreach ($agreementAdjustments as $adj) {
                $adj->invoice_id = $invoice->id;
                $adj->status = AgreementAdjustmentStatus::Applied;
                $adj->row_version += 1;
                $adj->save();

                $history = new AgreementAdjustmentHistory;
                $history->tenant_id = $context->tenantId;
                $history->adjustment_id = $adj->id;
                $history->from_status = AgreementAdjustmentStatus::Authorized;
                $history->to_status = AgreementAdjustmentStatus::Applied;
                $history->reason = 'Applied to Owner Settlement Bill '.$invoice->invoice_number;
                $history->user_id = $context->actorId;
                $history->save();
            }

            return $invoice;
        });
    }

    public function previewOwnerSettlementBill(
        AgreementContext $context,
        int $ownerAgreementId,
        array $input
    ): array {
        $this->authorization->assertBilling($context, AgreementKind::Owner, false);

        $startDate = (string) ($input['start_date'] ?? $input['period_from'] ?? '');
        $endDate = (string) ($input['end_date'] ?? $input['period_until'] ?? '');
        if ($startDate === '' || $endDate === '') {
            throw ValidationException::withMessages(['dates' => ['Specify both start date and end date for the owner settlement bill preview.']]);
        }

        $start = CarbonImmutable::parse($startDate)->startOfDay();
        $end = CarbonImmutable::parse($endDate)->endOfDay();
        if ($end->lessThan($start)) {
            throw ValidationException::withMessages(['end_date' => ['End date must be on or after start date.']]);
        }

        $agreement = OwnerAgreement::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->with(['party', 'vehicle'])
            ->findOrFail($ownerAgreementId);

        $whtRate = isset($input['wht_rate']) && is_numeric($input['wht_rate'])
            ? (string) $input['wht_rate']
            : '10.000000';

        // 1. Fetch base charges for period
        $baseCharges = OwnerBaseCharge::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->where('agreement_id', $agreement->id)
            ->whereNull('voided_at')
            ->where('period_from', '<=', $endDate)
            ->where('period_until', '>=', $startDate)
            ->get();

        $basePayable = '0.000000';
        foreach ($baseCharges as $charge) {
            $basePayable = $this->math->add($basePayable, (string) $charge->amount, 6);
        }

        // 2. Fetch usage charges for period (OT, mileage)
        $usageCharges = OwnerUsageCharge::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->where('agreement_id', $agreement->id)
            ->whereNull('voided_at')
            ->where('period_from', '<=', $endDate)
            ->where('period_until', '>=', $startDate)
            ->get();

        $usagePayable = '0.000000';
        foreach ($usageCharges as $charge) {
            $usagePayable = $this->math->add($usagePayable, (string) $charge->amount, 6);
        }

        // Also check unbilled owner BillingCycles if no owner base charges exist
        if ($baseCharges->isEmpty()) {
            $unbilledOwnerCycles = BillingCycle::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->where('owner_agreement_id', $agreement->id)
                ->where('period_from', '<=', $endDate)
                ->where('period_until', '>=', $startDate)
                ->whereNull('initial_invoice_id')
                ->whereNull('final_invoice_id')
                ->whereNull('supplementary_invoice_id')
                ->get();

            foreach ($unbilledOwnerCycles as $oCycle) {
                $basePayable = $this->math->add($basePayable, (string) $oCycle->base_rent_amount, 6);
                $cycleUsage = $this->math->add((string) $oCycle->excess_km_amount, (string) $oCycle->overtime_amount, 6);
                $cycleUsage = $this->math->add($cycleUsage, (string) $oCycle->night_out_amount, 6);
                $cycleUsage = $this->math->add($cycleUsage, (string) $oCycle->tolls_amount, 6);
                $cycleUsage = $this->math->add($cycleUsage, (string) $oCycle->additions_amount, 6);
                $usagePayable = $this->math->add($usagePayable, $cycleUsage, 6);
            }
        }

        // Pro-rate base rent if terms exist and base charges were empty
        if ($this->math->compare($basePayable, '0.000000') === 0 && isset($agreement->terms['base_rate'])) {
            $rate = (string) $agreement->terms['base_rate'];
            $daysInPeriod = max(1, $start->diffInDays($end) + 1);
            $daysInMonth = (int) $start->daysInMonth;
            if ($daysInPeriod >= $daysInMonth) {
                $basePayable = $rate;
            } else {
                $basePayable = $this->math->div($this->math->mul($rate, (string) $daysInPeriod, 6), (string) $daysInMonth, 6);
            }
        }

        // Agreement adjustments
        $agreementAdjustments = AgreementAdjustment::query()
            ->where('tenant_id', $context->tenantId)
            ->where('organization_unit_id', $context->organizationUnitId)
            ->where('owner_agreement_id', $agreement->id)
            ->where('status', AgreementAdjustmentStatus::Authorized->value)
            ->whereNull('invoice_id')
            ->get();

        $adjustmentAdditions = '0.000000';
        $adjustmentDeductions = '0.000000';
        foreach ($agreementAdjustments as $adj) {
            if ($adj->adjustment_type === AgreementAdjustmentType::DebitNote) {
                $adjustmentAdditions = $this->math->add($adjustmentAdditions, (string) $adj->amount, 6);
            } else {
                $adjustmentDeductions = $this->math->add($adjustmentDeductions, (string) $adj->amount, 6);
            }
        }

        $grossPayable = $this->math->add($basePayable, $usagePayable, 6);
        $grossPayable = $this->math->add($grossPayable, $adjustmentAdditions, 6);

        // 3. Calculate 10% Withholding Tax
        $whtAmount = $this->math->div($this->math->mul($grossPayable, $whtRate, 6), '100.000000', 6);

        // 4. Fetch maintenance expense chargebacks
        $expenseAllocations = RentalExpenseAllocation::query()
            ->where('tenant_id', $context->tenantId)
            ->where('target_owner_agreement_id', $agreement->id)
            ->where('status', RentalExpenseAllocationStatus::Confirmed->value)
            ->whereNull('invoice_id')
            ->get();

        $maintenanceDeductions = '0.000000';
        foreach ($expenseAllocations as $alloc) {
            $maintenanceDeductions = $this->math->add($maintenanceDeductions, (string) $alloc->total_amount, 6);
        }

        // Capping - ensure non-tax deductions do not exceed max(0, grossPayable - whtAmount)
        $maxDeductionsCapacity = $this->math->compare($grossPayable, $whtAmount) > 0
            ? $this->math->sub($grossPayable, $whtAmount, 6)
            : '0.000000';

        $applicableMaintenance = $this->math->compare($maintenanceDeductions, $maxDeductionsCapacity) > 0
            ? $maxDeductionsCapacity
            : $maintenanceDeductions;

        $remainingCapacityAfterMaint = $this->math->sub($maxDeductionsCapacity, $applicableMaintenance, 6);
        $applicableAdjDeductions = $this->math->compare($adjustmentDeductions, $remainingCapacityAfterMaint) > 0
            ? $remainingCapacityAfterMaint
            : $adjustmentDeductions;

        $totalDeductions = $this->math->add($whtAmount, $applicableMaintenance, 6);
        $totalDeductions = $this->math->add($totalDeductions, $applicableAdjDeductions, 6);

        $netPayable = $this->math->sub($grossPayable, $totalDeductions, 6);
        if ($this->math->compare($netPayable, '0.000000') < 0) {
            $netPayable = '0.000000';
        }

        $lines = [];
        $lineNum = 1;
        $vehicleNo = $agreement->vehicle?->registration_number ?? $agreement->vehicle?->vehicle_number ?? 'Fleet Vehicle';

        if ($baseCharges->isNotEmpty()) {
            foreach ($baseCharges as $charge) {
                $desc = 'Lessor Base Lease Rent · Charge #'.$charge->id.' ('.$charge->period_from.' To '.$charge->period_until.')';
                $lines[] = [
                    'line_number' => $lineNum++,
                    'description' => $desc,
                    'quantity' => '1.000000',
                    'unit_price' => (string) $charge->amount,
                    'discount_amount' => '0.000000',
                    'line_total' => (string) $charge->amount,
                ];
            }
        } elseif ($this->math->compare($basePayable, '0.000000') > 0) {
            $desc = 'Gross Vehicle Lease Rent · '.$agreement->reference.' · '.$vehicleNo.' ('.$start->format('d/m/Y').' To '.$end->format('d/m/Y').')';
            $lines[] = [
                'line_number' => $lineNum++,
                'description' => $desc,
                'quantity' => '1.000000',
                'unit_price' => $basePayable,
                'discount_amount' => '0.000000',
                'line_total' => $basePayable,
            ];
        }

        if ($usageCharges->isNotEmpty()) {
            foreach ($usageCharges as $charge) {
                $desc = 'Lessor Operational Usage & Overtime · Charge #'.$charge->id.' ('.$charge->period_from.' To '.$charge->period_until.')';
                $lines[] = [
                    'line_number' => $lineNum++,
                    'description' => $desc,
                    'quantity' => '1.000000',
                    'unit_price' => (string) $charge->amount,
                    'discount_amount' => '0.000000',
                    'line_total' => (string) $charge->amount,
                ];
            }
        } elseif ($this->math->compare($usagePayable, '0.000000') > 0) {
            $desc = 'Lessor Operational Usage Charges ('.$start->format('d/m/Y').' To '.$end->format('d/m/Y').')';
            $lines[] = [
                'line_number' => $lineNum++,
                'description' => $desc,
                'quantity' => '1.000000',
                'unit_price' => $usagePayable,
                'discount_amount' => '0.000000',
                'line_total' => $usagePayable,
            ];
        }

        foreach ($agreementAdjustments->where('adjustment_type', AgreementAdjustmentType::DebitNote) as $adj) {
            $desc = 'Lessor Addition · '.$adj->category->value.' ('.$adj->reason.')';
            $lines[] = [
                'line_number' => $lineNum++,
                'description' => $desc,
                'quantity' => '1.000000',
                'unit_price' => (string) $adj->amount,
                'discount_amount' => '0.000000',
                'line_total' => (string) $adj->amount,
            ];
        }

        $adjustments = [];
        if ($this->math->compare($whtAmount, '0.000000') > 0) {
            $adjustments[] = [
                'name' => 'Statutory Withholding Tax Deduction ('.rtrim(rtrim($whtRate, '0'), '.').'%)',
                'type' => 'withholding',
                'effect' => 'decrease',
                'amount' => $whtAmount,
            ];
        }

        if ($this->math->compare($applicableMaintenance, '0.000000') > 0) {
            $adjustments[] = [
                'name' => 'Fleet Maintenance Recovery',
                'type' => 'discount',
                'effect' => 'decrease',
                'amount' => $applicableMaintenance,
            ];
        }

        if ($this->math->compare($applicableAdjDeductions, '0.000000') > 0) {
            $adjustments[] = [
                'name' => 'Contractual Agreement Deductions',
                'type' => 'discount',
                'effect' => 'decrease',
                'amount' => $applicableAdjDeductions,
            ];
        }

        $partyCode = $agreement->party?->code ?? $agreement->party?->supplier_number ?? '-';
        $partyPhone = $agreement->party?->phone ?? $agreement->party?->mobile ?? null;

        return [
            'bill_number' => 'PROFORMA-SETTLE-'.$agreement->reference,
            'direction' => 'inbound',
            'is_preview' => true,
            'persisted' => false,
            'document_date' => now()->toDateString(),
            'party' => [
                'id' => $agreement->party?->id,
                'type' => 'supplier',
                'name' => $agreement->party?->display_name ?? $agreement->party?->name ?? 'Vehicle Owner',
                'code' => $partyCode,
                'phone' => $partyPhone,
            ],
            'supplier' => $agreement->party ? [
                'id' => $agreement->party->id,
                'name' => $agreement->party->display_name ?? $agreement->party->name,
                'code' => $partyCode,
                'phone' => $partyPhone,
            ] : null,
            'agreement' => [
                'id' => $agreement->id,
                'reference' => $agreement->reference,
                'starts_on' => $agreement->starts_on?->toDateString(),
                'ends_on' => $agreement->ends_on?->toDateString(),
                'vehicle' => $agreement->vehicle ? [
                    'id' => $agreement->vehicle->id,
                    'registration_number' => $vehicleNo,
                    'make' => $agreement->vehicle->make,
                    'model' => $agreement->vehicle->model,
                ] : null,
            ],
            'period' => [
                'from' => $startDate,
                'until' => $endDate,
            ],
            'period_from' => $startDate,
            'period_until' => $endDate,
            'is_preview' => true,
            'persisted' => false,
            'document_type' => 'owner_bill_simulation',
            'wht_rate' => $whtRate,
            'lines' => $lines,
            'line_items' => $lines,
            'adjustments' => $adjustments,
            'financial_breakdown' => [
                'gross_payable' => $grossPayable,
                'base_payable' => $basePayable,
                'usage_payable' => $usagePayable,
                'adjustment_additions' => $adjustmentAdditions,
                'wht_rate' => $whtRate,
                'wht_amount' => $whtAmount,
                'maintenance_deductions' => $applicableMaintenance,
                'adjustment_deductions' => $applicableAdjDeductions,
                'total_deductions' => $totalDeductions,
                'net_payable' => $netPayable,
            ],
            'breakdown' => [
                'gross_payable' => $grossPayable,
                'base_payable' => $basePayable,
                'usage_payable' => $usagePayable,
                'adjustment_additions' => $adjustmentAdditions,
                'wht_rate' => $whtRate,
                'wht_amount' => $whtAmount,
                'maintenance_deductions' => $applicableMaintenance,
                'adjustment_deductions' => $applicableAdjDeductions,
                'total_deductions' => $totalDeductions,
                'net_payable' => $netPayable,
            ],
            'notes' => 'Pro-forma settlement preview. Gross: '.$grossPayable.', WHT: '.$whtAmount.', Net Payable: '.$netPayable,
        ];
    }
}
