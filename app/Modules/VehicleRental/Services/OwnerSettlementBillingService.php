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
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\RentalExpenseAllocationStatus;
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
                    if (preg_match('/\((\d{2}\/\d{2}\/\d{4})\s+To\s+(\d{2}\/\d{2}\/\d{4})\)/', $line->description ?? '', $m)) {
                        $exStart = CarbonImmutable::createFromFormat('d/m/Y', $m[1])->startOfDay();
                        $exEnd = CarbonImmutable::createFromFormat('d/m/Y', $m[2])->endOfDay();
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

            $grossPayable = $this->math->add($basePayable, $usagePayable, 6);
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

            // Total deduction passed as discount
            $totalDeductions = $this->math->add($whtAmount, $maintenanceDeductions, 6);
            if ($this->math->compare($totalDeductions, $grossPayable) > 0) {
                $totalDeductions = $grossPayable;
            }

            $netPayable = $this->math->sub($grossPayable, $totalDeductions, 6);
            $invoiceDate = (string) ($input['invoice_date'] ?? now()->toDateString());
            $dueDate = ! empty($input['due_date']) ? (string) $input['due_date'] : null;
            $exchangeRate = (string) ($input['exchange_rate'] ?? '1.000000');

            $sourceType = 'vehicle_rental_owner_settlement';
            $sourceId = (int) $agreement->id;
            $vehicleNo = $agreement->vehicle?->registration_number ?? $agreement->vehicle?->vehicle_number ?? 'Fleet Vehicle';
            $description = 'Lessor Settlement · '.$agreement->reference.' · '.$vehicleNo.' ('.$start->format('d/m/Y').' To '.$end->format('d/m/Y').')';

            $notes = 'Owner Settlement AP Bill. Gross: '.$grossPayable.', WHT: '.$whtAmount.', Deductions: '.$maintenanceDeductions.', Net Payable: '.$netPayable;

            $hasWht = $this->math->compare($whtAmount, '0.000000') > 0;
            $hasMaintenance = $this->math->compare($maintenanceDeductions, '0.000000') > 0;

            $lines = [
                new InvoiceLineData(
                    lineNumber: 1,
                    description: 'Gross Vehicle Lease Rent · '.$agreement->reference.' · '.$vehicleNo.' ('.$start->format('d/m/Y').' To '.$end->format('d/m/Y').')',
                    quantity: self::WHOLE_CHARGE,
                    unitPrice: $grossPayable,
                    lineType: InvoiceLineType::Charge,
                    discountAmount: '0.000000',
                    sourceLineType: $sourceType,
                    sourceLineId: $sourceId,
                ),
            ];

            $lineNum = 2;
            if ($hasWht) {
                $lines[] = new InvoiceLineData(
                    lineNumber: $lineNum++,
                    description: 'Statutory Withholding Tax Deduction ('.rtrim(rtrim($whtRate, '0'), '.').'%) · Deduction: '.$whtAmount,
                    quantity: self::WHOLE_CHARGE,
                    unitPrice: '0.000000',
                    lineType: InvoiceLineType::Charge,
                    discountAmount: '0.000000',
                );
            }

            if ($hasMaintenance) {
                $lines[] = new InvoiceLineData(
                    lineNumber: $lineNum++,
                    description: 'Fleet Maintenance Recovery · Deduction: '.$maintenanceDeductions,
                    quantity: self::WHOLE_CHARGE,
                    unitPrice: '0.000000',
                    lineType: InvoiceLineType::Charge,
                    discountAmount: '0.000000',
                );
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
                    amount: $maintenanceDeductions,
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
                    credit: $maintenanceDeductions,
                    description: 'Fleet maintenance chargeback recovery',
                );
            }

            $postingLines[] = new InvoicePostingLineData(
                role: FinanceAccountRoleCode::Payable,
                credit: $netPayable,
                description: 'Net payable to lessor: '.$agreement->reference,
            );

            $postingPlan = new InvoicePostingPlanData(
                profile: FinancePostingProfileCode::SupplierRentalInvoice,
                postingDate: $invoiceDate,
                lines: $postingLines,
                description: $description,
            );

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
                        sourceUnitPrice: $grossPayable,
                        sourceLineTotal: $netPayable,
                        invoicedLineTotal: $netPayable,
                    ),
                ],
                postingPlan: $postingPlan,
            );

            $invoice = $this->invoiceService->create($source);

            // Link expense allocations to this invoice
            foreach ($expenseAllocations as $alloc) {
                $alloc->invoice_id = $invoice->id;
                $alloc->status = RentalExpenseAllocationStatus::Invoiced;
                $alloc->save();
            }

            return $invoice;
        });
    }
}
