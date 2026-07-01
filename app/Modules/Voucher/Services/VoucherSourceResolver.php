<?php

declare(strict_types=1);

namespace Modules\Voucher\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\Enums\JournalStatus;
use Modules\Finance\Enums\JournalType;
use Modules\Finance\Models\FinanceJournalEntry;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentReversal;
use Modules\Voucher\DTOs\VoucherPresentationData;
use Modules\Voucher\Enums\VoucherType;

final class VoucherSourceResolver
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly VoucherPermissionResolver $permissions,
        private readonly VoucherTypeRegistry $registry,
    ) {}

    public function resolve(
        VoucherType $type,
        int $sourceId,
        int $tenantId,
        ?int $organizationUnitId,
        ?string $sourceKind = null,
    ): VoucherPresentationData {
        return match ($type) {
            VoucherType::Receipt => $this->paymentVoucher($type, $sourceId, $tenantId, $organizationUnitId, PaymentDirection::Inbound),
            VoucherType::Payment => $this->paymentVoucher($type, $sourceId, $tenantId, $organizationUnitId, PaymentDirection::Outbound),
            VoucherType::Journal => $this->journalVoucher($type, $sourceId, $tenantId, $organizationUnitId, [JournalType::General]),
            VoucherType::Contra => $this->journalVoucher($type, $sourceId, $tenantId, $organizationUnitId, [JournalType::Contra]),
            VoucherType::Adjustment => $this->journalVoucher($type, $sourceId, $tenantId, $organizationUnitId, [JournalType::Adjustment]),
            VoucherType::OpeningBalance => $this->journalVoucher($type, $sourceId, $tenantId, $organizationUnitId, [JournalType::Opening]),
            VoucherType::Reversal => $this->reversalVoucher($sourceId, $tenantId, $organizationUnitId, $sourceKind),
        };
    }

    private function paymentVoucher(
        VoucherType $type,
        int $sourceId,
        int $tenantId,
        ?int $organizationUnitId,
        PaymentDirection $direction,
    ): VoucherPresentationData {
        $payment = $this->paymentScope($tenantId, $organizationUnitId)
            ->with(['lines', 'allocations', 'unappliedBalance', 'refunds', 'reversals'])
            ->where('direction', $direction->value)
            ->findOrFail($sourceId);

        return new VoucherPresentationData($this->paymentPayload($type, $payment));
    }

    private function journalVoucher(
        VoucherType $type,
        int $sourceId,
        int $tenantId,
        ?int $organizationUnitId,
        array $journalTypes,
    ): VoucherPresentationData {
        $journal = $this->journalScope($tenantId, $organizationUnitId)
            ->with([
                'currency',
                'lines.account',
                'lines.dimension',
                'ledgerEntries',
                'reversalOf',
                'reversals',
            ])
            ->whereIn('journal_type', array_map(
                static fn (JournalType $journalType): string => $journalType->value,
                $journalTypes,
            ))
            ->findOrFail($sourceId);

        return new VoucherPresentationData($this->journalPayload($type, $journal));
    }

    private function reversalVoucher(
        int $sourceId,
        int $tenantId,
        ?int $organizationUnitId,
        ?string $sourceKind,
    ): VoucherPresentationData {
        if ($sourceKind === 'payment_reversal') {
            return $this->paymentReversalVoucher($sourceId, $tenantId, $organizationUnitId);
        }
        if ($sourceKind === 'finance_journal') {
            return $this->journalVoucher(VoucherType::Reversal, $sourceId, $tenantId, $organizationUnitId, [JournalType::Reversal]);
        }

        $paymentReversal = $this->paymentReversalScope($tenantId, $organizationUnitId)
            ->with(['payment.lines', 'payment.allocations', 'payment.reversals'])
            ->find($sourceId);

        if ($paymentReversal instanceof PaymentReversal) {
            return new VoucherPresentationData($this->paymentReversalPayload($paymentReversal));
        }

        return $this->journalVoucher(VoucherType::Reversal, $sourceId, $tenantId, $organizationUnitId, [JournalType::Reversal]);
    }

    private function paymentReversalVoucher(
        int $sourceId,
        int $tenantId,
        ?int $organizationUnitId,
    ): VoucherPresentationData {
        $reversal = $this->paymentReversalScope($tenantId, $organizationUnitId)
            ->with(['payment.lines', 'payment.allocations', 'payment.reversals'])
            ->findOrFail($sourceId);

        return new VoucherPresentationData($this->paymentReversalPayload($reversal));
    }

    private function paymentPayload(VoucherType $type, Payment $payment): array
    {
        $documentStatus = (string) $this->enum($payment->document_status);
        $postingStatus = (string) $this->enum($payment->posting_status);
        $partyName = $payment->party_name_snapshot ?? $payment->payee_name;
        $total = $this->math->normalize((string) $payment->total_amount);
        $exchangeRate = $this->math->normalize((string) $payment->exchange_rate);

        return [
            'voucher_type' => $type->value,
            'voucher_label' => $this->paymentLabel($type, $payment),
            'voucher_number' => (string) $payment->payment_number,
            'voucher_date' => $payment->payment_date?->toDateString(),
            'source_module' => 'Payment',
            'source_kind' => 'payment',
            'source_type' => 'payment',
            'source_id' => (int) $payment->getKey(),
            'source_document_number' => (string) $payment->payment_number,
            'tenant_id' => (int) $payment->tenant_id,
            'organization_unit_id' => $payment->organization_unit_id,
            'financial_year_id' => null,
            'financial_period_id' => null,
            'payer_or_payee' => $partyName ?? $payment->party_type,
            'party_type' => $payment->party_type,
            'party_name' => $partyName,
            'currency_id' => $payment->currency_id,
            'currency' => $payment->currency_code_snapshot,
            'exchange_rate' => $exchangeRate,
            'transaction_amount' => $total,
            'base_currency_amount' => $this->math->mul($total, $exchangeRate),
            'allocated_amount' => $this->math->normalize((string) $payment->allocated_amount),
            'unallocated_amount' => $this->math->normalize((string) $payment->unapplied_amount),
            'document_status' => $documentStatus,
            'approval_status' => $documentStatus,
            'allocation_status' => $this->enum($payment->allocation_status),
            'posting_status' => $postingStatus,
            'instrument_status' => $this->enum($payment->instrument_status),
            'narration' => $payment->notes,
            'external_references' => [
                'reference_number' => $payment->reference_number,
                'source_type' => $payment->source_type,
                'source_id' => $payment->source_id,
            ],
            'invoice_or_payable_references' => $this->paymentAllocations($payment),
            'payment_lines' => $this->paymentLines($payment),
            'journal_lines' => [],
            'tax_and_withholding' => [],
            'reversal_information' => $this->paymentReversalInfo($payment),
            'prepared_by' => $payment->created_by,
            'approved_by' => $payment->approved_by,
            'posted_by' => $payment->posted_by,
            'created_at' => $payment->created_at?->toIso8601String(),
            'updated_at' => $payment->updated_at?->toIso8601String(),
            'available_actions' => $this->permissions->forPayment(
                $documentStatus,
                $postingStatus,
                $payment->reversals->isNotEmpty(),
            ),
            'source_document_url' => '/payments/'.(string) $payment->getKey(),
            'print_available' => true,
            'print_url' => '/api/v1/vouchers/'.$type->value.'/'.(string) $payment->getKey().'/print?source_kind=payment',
        ];
    }

    private function paymentReversalPayload(PaymentReversal $reversal): array
    {
        $payment = $reversal->payment;
        if (! $payment instanceof Payment) {
            throw (new ModelNotFoundException())->setModel(Payment::class);
        }

        $payload = $this->paymentPayload(VoucherType::Reversal, $payment);
        $payload['voucher_number'] = (string) $reversal->reversal_number;
        $payload['voucher_date'] = $reversal->reversal_date?->toDateString();
        $payload['source_kind'] = 'payment_reversal';
        $payload['source_type'] = 'payment_reversal';
        $payload['source_id'] = (int) $reversal->getKey();
        $payload['source_document_number'] = (string) $payment->payment_number;
        $payload['transaction_amount'] = $this->math->normalize((string) $reversal->reversed_amount);
        $payload['base_currency_amount'] = $this->math->mul(
            (string) $reversal->reversed_amount,
            (string) $payment->exchange_rate,
        );
        $payload['document_status'] = 'reversed';
        $payload['approval_status'] = 'reversed';
        $payload['allocation_status'] = 'unallocated';
        $payload['posting_status'] = 'reversed';
        $payload['instrument_status'] = 'reversed';
        $payload['narration'] = $reversal->reason;
        $payload['reversal_information'] = [
            'original_source_module' => 'Payment',
            'original_source_id' => (int) $payment->getKey(),
            'original_number' => (string) $payment->payment_number,
            'reversal_number' => (string) $reversal->reversal_number,
            'reversal_date' => $reversal->reversal_date?->toDateString(),
            'reason' => $reversal->reason,
            'reversed_by' => $reversal->reversed_by,
        ];
        $payload['available_actions'] = ['view_source', 'print'];
        $payload['source_document_url'] = '/payments/'.(string) $payment->getKey();
        $payload['print_url'] = '/api/v1/vouchers/reversal_voucher/'.(string) $reversal->getKey().'/print?source_kind=payment_reversal';

        return $payload;
    }

    private function journalPayload(VoucherType $type, FinanceJournalEntry $journal): array
    {
        $status = (string) $this->enum($journal->status);
        $total = $this->math->normalize((string) $journal->total_debit);
        $exchangeRate = $this->math->normalize((string) $journal->exchange_rate);

        return [
            'voucher_type' => $type->value,
            'voucher_label' => $this->registry->get($type)['label'],
            'voucher_number' => (string) $journal->journal_number,
            'voucher_date' => $journal->journal_date?->toDateString(),
            'source_module' => 'Finance',
            'source_kind' => 'finance_journal',
            'source_type' => 'finance_journal',
            'source_id' => (int) $journal->getKey(),
            'source_document_number' => $journal->source_number ?: $journal->journal_number,
            'tenant_id' => (int) $journal->tenant_id,
            'organization_unit_id' => $journal->organization_unit_id,
            'financial_year_id' => null,
            'financial_period_id' => null,
            'financial_year' => null,
            'financial_period' => null,
            'payer_or_payee' => null,
            'party_type' => null,
            'party_name' => null,
            'currency_id' => $journal->currency_id,
            'currency' => $journal->currency?->code,
            'exchange_rate' => $exchangeRate,
            'transaction_amount' => $total,
            'base_currency_amount' => $this->math->mul($total, $exchangeRate),
            'allocated_amount' => '0.000000',
            'unallocated_amount' => '0.000000',
            'document_status' => $this->journalDocumentStatus($status),
            'approval_status' => $status,
            'allocation_status' => null,
            'posting_status' => $this->journalPostingStatus($status),
            'instrument_status' => null,
            'narration' => $journal->description,
            'external_references' => [
                'source_module' => $journal->source_module,
                'source_type' => $journal->source_type,
                'source_id' => $journal->source_id,
                'source_number' => $journal->source_number,
            ],
            'invoice_or_payable_references' => [],
            'payment_lines' => [],
            'journal_lines' => $this->journalLines($journal),
            'tax_and_withholding' => [],
            'reversal_information' => $this->journalReversalInfo($journal),
            'prepared_by' => $journal->created_by,
            'approved_by' => null,
            'posted_by' => $journal->posted_by,
            'created_at' => $journal->created_at?->toIso8601String(),
            'updated_at' => $journal->updated_at?->toIso8601String(),
            'available_actions' => $this->permissions->forJournal($status, $journal->reversals->isNotEmpty()),
            'source_document_url' => '/finance/journals/'.(string) $journal->getKey(),
            'print_available' => true,
            'print_url' => '/api/v1/vouchers/'.$type->value.'/'.(string) $journal->getKey().'/print?source_kind=finance_journal',
        ];
    }

    private function paymentScope(int $tenantId, ?int $organizationUnitId): Builder
    {
        return Payment::query()
            ->where('tenant_id', $tenantId)
            ->when($organizationUnitId === null, fn (Builder $query) => $query->whereNull('organization_unit_id'))
            ->when($organizationUnitId !== null, fn (Builder $query) => $query->where('organization_unit_id', $organizationUnitId));
    }

    private function paymentReversalScope(int $tenantId, ?int $organizationUnitId): Builder
    {
        return PaymentReversal::query()
            ->where('tenant_id', $tenantId)
            ->when($organizationUnitId === null, fn (Builder $query) => $query->whereNull('organization_unit_id'))
            ->when($organizationUnitId !== null, fn (Builder $query) => $query->where('organization_unit_id', $organizationUnitId));
    }

    private function journalScope(int $tenantId, ?int $organizationUnitId): Builder
    {
        return FinanceJournalEntry::query()
            ->where('tenant_id', $tenantId)
            ->when($organizationUnitId === null, fn (Builder $query) => $query->whereNull('organization_unit_id'))
            ->when($organizationUnitId !== null, fn (Builder $query) => $query->where('organization_unit_id', $organizationUnitId));
    }

    private function paymentLabel(VoucherType $type, Payment $payment): string
    {
        $paymentType = $payment->payment_type instanceof PaymentType
            ? $payment->payment_type
            : PaymentType::from((string) $payment->payment_type);

        if ($paymentType === PaymentType::RentalReceipt) {
            return 'Rental Receipt Voucher';
        }

        $sourceHint = strtolower((string) $payment->source_type.' '.json_encode($payment->metadata ?? []));
        if ($type === VoucherType::Payment && str_contains($sourceHint, 'rental')) {
            return 'Rental Owner Payment Voucher';
        }
        if (str_contains(strtolower((string) $payment->party_type), 'customer')) {
            return $type === VoucherType::Receipt ? 'Customer Receipt Voucher' : 'Customer Refund Voucher';
        }
        if (str_contains(strtolower((string) $payment->party_type), 'supplier')) {
            return 'Supplier Payment Voucher';
        }
        if (str_contains(strtolower((string) $payment->party_type), 'employee')) {
            return $paymentType === PaymentType::Advance ? 'Employee Advance Voucher' : 'Expense Reimbursement Voucher';
        }

        return $type->label();
    }

    private function paymentLines(Payment $payment): array
    {
        return $payment->lines->map(fn ($line): array => [
            'method' => $line->payment_method_name_snapshot,
            'method_code' => $line->payment_method_code_snapshot,
            'method_type' => $line->payment_method_type_snapshot,
            'reference_number' => $line->reference_number,
            'amount' => $this->math->normalize((string) $line->amount),
            'cleared_amount' => $this->math->normalize((string) $line->cleared_amount),
            'status' => (string) $line->status,
            'instrument_direction' => $line->instrument_direction,
            'external_bank' => $line->external_bank_name,
            'external_branch' => $line->external_bank_branch,
            'instrument_number' => $line->instrument_number,
            'instrument_date' => $line->instrument_date?->toDateString(),
            'deposit_date' => $line->deposit_date?->toDateString(),
            'realized_date' => $line->realized_date?->toDateString(),
            'clearing_date' => $line->clearing_date?->toDateString(),
            'bounced_date' => $line->bounced_date?->toDateString(),
            'returned_date' => $line->returned_date?->toDateString(),
            'cancellation_reason' => $line->cancellation_reason,
            'notes' => $line->notes,
        ])->values()->all();
    }

    private function paymentAllocations(Payment $payment): array
    {
        return $payment->allocations->map(fn ($allocation): array => [
            'invoice_number' => $allocation->invoice_number_snapshot,
            'invoice_date' => $allocation->invoice_date_snapshot?->toDateString(),
            'invoice_currency_code' => $allocation->invoice_currency_code_snapshot,
            'invoice_total' => $this->math->normalize((string) $allocation->invoice_total),
            'invoice_balance_before' => $this->math->normalize((string) $allocation->invoice_balance_before),
            'allocated_amount' => $this->math->normalize((string) $allocation->allocated_amount),
            'invoice_balance_after' => $this->math->normalize((string) $allocation->invoice_balance_after),
            'allocation_date' => $allocation->allocation_date?->toDateString(),
            'status' => $this->enum($allocation->status),
        ])->values()->all();
    }

    private function journalLines(FinanceJournalEntry $journal): array
    {
        return $journal->lines->map(fn ($line): array => [
            'line_number' => (int) $line->line_number,
            'account_code' => $line->account?->code,
            'account_name' => $line->account?->name,
            'description' => $line->description,
            'debit' => $this->math->normalize((string) $line->debit),
            'credit' => $this->math->normalize((string) $line->credit),
            'dimension' => $line->dimension?->name,
            'source_line_type' => $line->source_line_type,
        ])->values()->all();
    }

    private function paymentReversalInfo(Payment $payment): ?array
    {
        $reversal = $payment->reversals->first();
        if (! $reversal instanceof PaymentReversal) {
            return null;
        }

        return [
            'reversal_source_kind' => 'payment_reversal',
            'reversal_source_id' => (int) $reversal->getKey(),
            'reversal_number' => (string) $reversal->reversal_number,
            'reversal_date' => $reversal->reversal_date?->toDateString(),
            'reason' => $reversal->reason,
            'reversed_by' => $reversal->reversed_by,
        ];
    }

    private function journalReversalInfo(FinanceJournalEntry $journal): ?array
    {
        if ($journal->reversalOf instanceof FinanceJournalEntry) {
            return [
                'original_source_module' => 'Finance',
                'original_source_id' => (int) $journal->reversalOf->getKey(),
                'original_number' => (string) $journal->reversalOf->journal_number,
                'reason' => $journal->reversal_reason,
            ];
        }

        $reversal = $journal->reversals->first();
        if (! $reversal instanceof FinanceJournalEntry) {
            return null;
        }

        return [
            'reversal_source_kind' => 'finance_journal',
            'reversal_source_id' => (int) $reversal->getKey(),
            'reversal_number' => (string) $reversal->journal_number,
            'reversal_date' => $reversal->journal_date?->toDateString(),
            'reason' => $reversal->reversal_reason,
            'reversed_by' => $reversal->reversed_by,
        ];
    }

    private function journalDocumentStatus(string $status): string
    {
        return match ($status) {
            JournalStatus::Cancelled->value,
            JournalStatus::Void->value => 'voided',
            JournalStatus::Reversed->value => 'reversed',
            default => 'approved',
        };
    }

    private function journalPostingStatus(string $status): string
    {
        return match ($status) {
            JournalStatus::Posted->value => 'posted',
            JournalStatus::Reversed->value => 'reversed',
            default => 'not_posted',
        };
    }

    private function enum(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }
}
