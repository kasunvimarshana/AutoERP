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

            $source = new CreateInvoiceData(
                tenantId: $context->tenantId,
                organizationUnitId: $context->organizationUnitId,
                invoiceType: InvoiceType::Rental,
                direction: InvoiceDirection::Inbound,
                invoiceDate: $invoiceDate,
                dueDate: $dueDate,
                currencyId: $agreement->currency_id,
                exchangeRate: $exchangeRate,
                partyType: InvoicePartyType::Supplier->value,
                partyId: $agreement->supplier_id,
                createdBy: $context->actorId,
                notes: $notes,
                lines: [
                    new InvoiceLineData(
                        lineNumber: 1,
                        description: $description,
                        quantity: self::WHOLE_CHARGE,
                        unitPrice: $grossPayable,
                        lineType: InvoiceLineType::Charge,
                        discountAmount: $totalDeductions,
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
                        sourceDocumentNumber: $agreement->reference.'-SETTLE-'.CarbonImmutable::parse($startDate)->format('Ym'),
                        sourceDocumentDate: $invoiceDate,
                        sourceSubtotal: $netPayable,
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
            );

            $invoice = $this->invoiceService->create(
                $this->invoiceFactory->prepare(
                    $source,
                    FinancePostingProfileCode::SupplierRentalInvoice,
                    FinanceAccountRoleCode::RentalExpense,
                )
            );

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
