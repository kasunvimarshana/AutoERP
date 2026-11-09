<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\InvoiceSourceService;
use Modules\Payment\Enums\PaymentSourceType;
use Modules\Payment\Services\PaymentSourceService;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\BaseChargeSource;
use Modules\VehicleRental\Enums\RentalExpenseAllocationStatus;
use Modules\VehicleRental\Enums\RentalExpenseAllocationType;
use Modules\VehicleRental\Enums\UsageChargeSource;
use Modules\VehicleRental\Models\CustomerBaseCharge;
use Modules\VehicleRental\Models\CustomerUsageCharge;
use Modules\VehicleRental\Models\OwnerBaseCharge;
use Modules\VehicleRental\Models\OwnerUsageCharge;
use Modules\VehicleRental\Models\RentalCharge;
use Modules\VehicleRental\Models\RentalExpenseAllocation;

final class AgreementFinancialSummaryService
{
    public function __construct(
        private readonly AgreementService $agreements,
        private readonly RentalAuthorization $authorization,
        private readonly DecimalMath $math,
        private readonly InvoiceSourceService $invoiceSources,
        private readonly PaymentSourceService $paymentSources,
    ) {}

    public function generate(AgreementKind $kind, AgreementContext $context, int $agreementId): array
    {
        $this->authorization->assert($context, $kind, false);
        $agreement = $this->agreements->find($kind, $context, $agreementId);

        $isCustomer = $kind === AgreementKind::Customer;

        // 1. Base Charges
        $baseClass = $isCustomer ? CustomerBaseCharge::class : OwnerBaseCharge::class;
        $baseCharges = $baseClass::query()
            ->where('tenant_id', $context->tenantId)
            ->where('organization_unit_id', $context->organizationUnitId)
            ->where('agreement_id', $agreement->id)
            ->orderByDesc('id')
            ->get();
        $totalBaseCharges = (string) $baseCharges->whereNull('voided_at')->sum('amount');
        $baseSourceType = BaseChargeSource::forKind($kind)->value;
        $baseDocMap = $this->invoiceSources->documents($context->tenantId, $context->organizationUnitId, $baseSourceType, $baseCharges->pluck('id')->all());

        // 2. Usage Charges
        $usageClass = $isCustomer ? CustomerUsageCharge::class : OwnerUsageCharge::class;
        $usageCharges = $usageClass::query()
            ->where('tenant_id', $context->tenantId)
            ->where('organization_unit_id', $context->organizationUnitId)
            ->where('agreement_id', $agreement->id)
            ->orderByDesc('id')
            ->get();
        $totalUsageCharges = (string) $usageCharges->whereNull('voided_at')->sum('amount');
        $usageSourceType = UsageChargeSource::forKind($kind)->value;
        $usageDocMap = $this->invoiceSources->documents($context->tenantId, $context->organizationUnitId, $usageSourceType, $usageCharges->pluck('id')->all());

        // 3. Expense Allocations (Customer Recoveries or Owner Deductions)
        $allocationsQuery = RentalExpenseAllocation::query()
            ->where('tenant_id', $context->tenantId)
            ->where('organization_unit_id', $context->organizationUnitId)
            ->with(['expense.currency', 'invoice.balance']);
        if ($isCustomer) {
            $allocationsQuery->where('target_customer_agreement_id', $agreement->id)
                ->where('allocation_type', RentalExpenseAllocationType::CustomerRecovery->value);
        } else {
            $allocationsQuery->where('target_owner_agreement_id', $agreement->id)
                ->where('allocation_type', RentalExpenseAllocationType::OwnerDeduction->value);
        }
        $allocations = $allocationsQuery->orderByDesc('id')->get();
        $totalAllocations = (string) $allocations->where('status', RentalExpenseAllocationStatus::Confirmed)->sum('total_amount');

        // 4. Linked Financial Invoices, Credit Notes, and Debit Notes
        $partyType = $isCustomer ? 'customer' : 'supplier';
        $partyId = $isCustomer ? $agreement->customer_id : $agreement->supplier_id;
        $invoices = Invoice::query()
            ->where('tenant_id', $context->tenantId)
            ->where('organization_unit_id', $context->organizationUnitId)
            ->where('party_type', $partyType)
            ->where('party_id', $partyId)
            ->whereHas('sources', fn ($q) => $q->where('source_document_number', $agreement->reference))
            ->with(['balance', 'sources', 'lines'])
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->get();

        $totalInvoiced = '0.000000';
        $totalCreditNotes = '0.000000';
        $totalDebitNotes = '0.000000';
        $totalPaid = '0.000000';
        $totalCreditAllocated = '0.000000';
        $totalRemainingBalance = '0.000000';
        $totalUnallocatedCredit = '0.000000';

        $documents = [];
        foreach ($invoices as $inv) {
            $type = $inv->invoice_type instanceof InvoiceType ? $inv->invoice_type->value : (string) $inv->invoice_type;
            $grand = (string) $inv->grand_total;
            $paid = (string) ($inv->paid_total ?? $inv->balance?->paid_amount ?? '0.000000');
            $credit = (string) ($inv->credit_total ?? $inv->balance?->credit_allocated_amount ?? '0.000000');
            $rem = (string) ($inv->balance_due ?? $inv->balance?->remaining_amount ?? '0.000000');

            if ($type === InvoiceType::Credit->value) {
                $totalCreditNotes = $this->math->add($totalCreditNotes, $grand);
            } elseif ($type === InvoiceType::Debit->value) {
                $totalDebitNotes = $this->math->add($totalDebitNotes, $grand);
                $totalInvoiced = $this->math->add($totalInvoiced, $grand);
            } else {
                $totalInvoiced = $this->math->add($totalInvoiced, $grand);
            }

            $invStatus = $inv->status instanceof InvoiceStatus ? $inv->status : InvoiceStatus::tryFrom((string) $inv->status);
            if (! in_array($invStatus, [InvoiceStatus::Cancelled, InvoiceStatus::Void, InvoiceStatus::Reversed], true)) {
                $totalPaid = $this->math->add($totalPaid, $paid);
                $totalCreditAllocated = $this->math->add($totalCreditAllocated, $credit);
                if ($type === InvoiceType::Credit->value) {
                    $totalUnallocatedCredit = $this->math->add($totalUnallocatedCredit, $rem);
                } else {
                    $totalRemainingBalance = $this->math->add($totalRemainingBalance, $rem);
                }
            }

            $docKind = match ($type) {
                InvoiceType::Credit->value => 'credit_note',
                InvoiceType::Debit->value => 'debit_note',
                default => $inv->document_kind?->value ?? ($isCustomer ? 'invoice' : 'owner_payable_voucher'),
            };

            $documents[] = [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'invoice_date' => $inv->invoice_date->toDateString(),
                'due_date' => $inv->due_date?->toDateString(),
                'invoice_type' => $type,
                'document_kind' => $docKind,
                'status' => $inv->status instanceof InvoiceStatus ? $inv->status->value : (string) $inv->status,
                'grand_total' => $grand,
                'paid_total' => $paid,
                'credit_total' => $credit,
                'balance_due' => $rem,
                'notes' => $inv->notes,
            ];
        }

        // 5. Deposit Summary (for customer agreements)
        $depositSummary = null;
        if ($isCustomer) {
            $paymentDocs = $this->paymentSources->documents($context->tenantId, $context->organizationUnitId, PaymentSourceType::RentalAgreementDeposit, $agreement->id);
            $netDeposit = $this->paymentSources->netReceipts($paymentDocs);
            $requiredDeposit = $agreement->terms['deposit_requirement'] ?? null;
            $depositSummary = [
                'requirement' => $requiredDeposit,
                'net_receipts' => $netDeposit,
                'remaining' => $requiredDeposit !== null ? $this->math->sub((string) $requiredDeposit, $netDeposit) : null,
                'receipts_count' => $paymentDocs->count(),
            ];
        }

        return [
            'agreement' => [
                'id' => $agreement->id,
                'reference' => $agreement->reference,
                'kind' => $kind->value,
                'status' => $agreement->status->value,
                'currency' => [
                    'id' => $agreement->currency_id,
                    'code' => $agreement->currency_code_snapshot,
                ],
                'party' => [
                    'id' => $agreement->party->getKey(),
                    'name' => $agreement->party_name_snapshot,
                    'code' => $agreement->party_code_snapshot,
                ],
            ],
            'summary' => [
                'total_base_charges' => $totalBaseCharges,
                'total_usage_charges' => $totalUsageCharges,
                'total_allocations' => $totalAllocations,
                'total_invoiced' => $totalInvoiced,
                'total_credit_notes' => $totalCreditNotes,
                'total_debit_notes' => $totalDebitNotes,
                'total_paid' => $totalPaid,
                'total_credit_allocated' => $totalCreditAllocated,
                'total_remaining_balance' => $totalRemainingBalance,
                'total_unallocated_credit' => $totalUnallocatedCredit,
            ],
            'base_charges' => $baseCharges->map(fn (RentalCharge $c) => [
                'id' => $c->id,
                'from' => $c->period_from,
                'until' => $c->period_until,
                'amount' => (string) $c->amount,
                'voided_at' => $c->voided_at,
                'invoices' => $baseDocMap[$c->id] ?? [],
            ]),
            'usage_charges' => $usageCharges->map(fn (RentalCharge $c) => [
                'id' => $c->id,
                'component' => $c->component,
                'from' => $c->period_from,
                'until' => $c->period_until,
                'amount' => (string) $c->amount,
                'voided_at' => $c->voided_at,
                'invoices' => $usageDocMap[$c->id] ?? [],
            ]),
            'allocations' => $allocations->map(fn (RentalExpenseAllocation $a) => [
                'id' => $a->id,
                'row_version' => $a->row_version,
                'sequence' => $a->sequence,
                'allocation_type' => $a->allocation_type->value,
                'allocation_type_label' => $a->allocation_type->label(),
                'net_amount' => (string) $a->net_amount,
                'total_amount' => (string) $a->total_amount,
                'status' => $a->status->value,
                'expense' => [
                    'id' => $a->expense->id,
                    'expense_number' => $a->expense->expense_number,
                    'expense_type' => $a->expense->expense_type->label(),
                    'expense_date' => $a->expense->expense_date->toDateString(),
                ],
                'invoice' => $a->invoice ? [
                    'id' => $a->invoice->id,
                    'invoice_number' => $a->invoice->invoice_number,
                    'status' => $a->invoice->status instanceof InvoiceStatus ? $a->invoice->status->value : (string) $a->invoice->status,
                ] : null,
            ]),
            'documents' => $documents,
            'deposit' => $depositSummary,
        ];
    }
}
