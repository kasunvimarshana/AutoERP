<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentAllocation;
use Modules\Payment\Models\PaymentLine;

final class PaymentReceiptService
{
    public function __construct(
        private readonly AmountInWordsService $words,
        private readonly DecimalMath $math,
    ) {}

    public function getReceiptData(Payment $payment): array
    {
        $payment->loadMissing([
            'currency',
            'lines.paymentMethod',
            'allocations',
            'unappliedBalance',
        ]);

        $isInbound = $payment->direction === PaymentDirection::Inbound || $payment->direction->value === PaymentDirection::Inbound->value;
        $title = $isInbound ? 'OFFICIAL PAYMENT RECEIPT' : 'DISBURSEMENT VOUCHER';
        $partyRole = $isInbound ? 'Received From' : 'Paid To';

        $curCode = $payment->currency_code_snapshot ?? $payment->currency?->code ?? 'LKR';
        $curSymbol = $payment->currency_symbol_snapshot ?? $payment->currency?->symbol ?? 'Rs.';

        $invoiceSources = [];
        if ($payment->allocations->isNotEmpty()) {
            $invoiceIds = $payment->allocations->pluck('invoice_id')->filter()->all();
            if (! empty($invoiceIds)) {
                $invoiceSources = \Illuminate\Support\Facades\DB::table('invoice_sources')
                    ->where('tenant_id', $payment->tenant_id)
                    ->whereIn('invoice_id', $invoiceIds)
                    ->whereNotNull('source_document_number')
                    ->pluck('source_document_number', 'invoice_id')
                    ->all();
            }
        }

        $lines = $payment->lines->map(function (PaymentLine $line) {
            $metadata = is_array($line->metadata) ? $line->metadata : [];
            $methodType = $line->paymentMethod?->method_type instanceof \BackedEnum
                ? $line->paymentMethod->method_type->value
                : (string) ($line->paymentMethod?->method_type ?? 'other');

            $details = [];
            if (! empty($line->reference_number)) {
                $details[] = 'Ref: '.$line->reference_number;
            }
            if (! empty($line->external_bank_name)) {
                $details[] = 'Bank: '.$line->external_bank_name.(! empty($line->external_bank_branch) ? ' ('.$line->external_bank_branch.')' : '');
            }
            if (! empty($line->instrument_number)) {
                $details[] = 'Cheque/Inst #: '.$line->instrument_number.(! empty($line->instrument_date) ? ' dt. '.$line->instrument_date->toDateString() : '');
            }
            if (! empty($metadata['card_brand'])) {
                $details[] = 'Card: '.strtoupper((string) $metadata['card_brand']);
            }

            return [
                'id' => $line->id,
                'method_name' => $line->paymentMethod?->name ?? ucfirst($methodType),
                'method_type' => $methodType,
                'amount' => (string) $line->amount,
                'reference' => $line->reference_number,
                'details' => implode(' · ', $details),
            ];
        })->all();

        $missingSnapshots = $payment->allocations->filter(
            fn (PaymentAllocation $a) => empty($a->invoice_number_snapshot) || $a->invoice_date_snapshot === null
        );
        $legacyInvoices = [];
        if ($missingSnapshots->isNotEmpty()) {
            $missingIds = $missingSnapshots->pluck('invoice_id')->filter()->all();
            if (! empty($missingIds)) {
                $legacyInvoices = \Illuminate\Support\Facades\DB::table('invoices')
                    ->where('tenant_id', $payment->tenant_id)
                    ->whereIn('id', $missingIds)
                    ->get(['id', 'invoice_number', 'invoice_date'])
                    ->keyBy('id')
                    ->all();
            }
        }

        $allocations = $payment->allocations->map(function (PaymentAllocation $alloc) use ($legacyInvoices, $invoiceSources) {
            $legacy = $legacyInvoices[$alloc->invoice_id] ?? null;
            $invNo = ! empty($alloc->invoice_number_snapshot)
                ? $alloc->invoice_number_snapshot
                : ($legacy?->invoice_number ?? "INV-#{$alloc->invoice_id}");
            $invDate = $alloc->invoice_date_snapshot?->toDateString()
                ?? ($legacy?->invoice_date ? \Carbon\Carbon::parse($legacy->invoice_date)->toDateString() : '—');

            $allocMeta = is_array($alloc->metadata) ? $alloc->metadata : [];
            $allocAgrRef = $allocMeta['agreement_reference'] ?? ($invoiceSources[$alloc->invoice_id] ?? null);

            return [
                'id' => $alloc->id,
                'invoice_id' => $alloc->invoice_id,
                'invoice_number' => $invNo,
                'agreement_reference' => $allocAgrRef,
                'invoice_date' => $invDate,
                'invoice_total' => (string) $alloc->invoice_total,
                'balance_before' => (string) $alloc->invoice_balance_before,
                'allocated_amount' => (string) $alloc->allocated_amount,
                'balance_after' => (string) $alloc->invoice_balance_after,
                'status' => $alloc->status,
            ];
        })->all();

        $agreementRefs = [];
        if (! empty($payment->metadata['agreement_references']) && is_array($payment->metadata['agreement_references'])) {
            $agreementRefs = $payment->metadata['agreement_references'];
        } elseif (! empty($payment->metadata['agreement_reference'])) {
            $agreementRefs[] = (string) $payment->metadata['agreement_reference'];
        }
        foreach ($allocations as $allocItem) {
            if (! empty($allocItem['agreement_reference'])) {
                $agreementRefs[] = (string) $allocItem['agreement_reference'];
            }
        }
        if (empty($agreementRefs) && ! empty($invoiceSources)) {
            $agreementRefs = array_values(array_unique(array_filter($invoiceSources)));
        }
        if (empty($agreementRefs) && ! empty($payment->source_id)) {
            $ref = \Illuminate\Support\Facades\DB::table('customer_agreements')
                ->where('tenant_id', $payment->tenant_id)
                ->where('id', $payment->source_id)
                ->value('reference')
                ?? \Illuminate\Support\Facades\DB::table('owner_agreements')
                    ->where('tenant_id', $payment->tenant_id)
                    ->where('id', $payment->source_id)
                    ->value('reference');
            if ($ref) {
                $agreementRefs[] = $ref;
            }
        }
        $agreementRefs = array_values(array_unique(array_filter($agreementRefs)));
        $agreementRef = ! empty($agreementRefs) ? implode(', ', $agreementRefs) : null;

        $unapplied = (string) ($payment->unapplied_amount ?? $payment->unappliedBalance?->unapplied_amount ?? '0.000000');
        $wordsText = '';
        try {
            $wordsText = ucwords($this->words->convert((string) $payment->total_amount)).' Only';
        } catch (\Throwable) {
            $wordsText = '';
        }

        $partyName = $payment->party_name_snapshot;
        $partyCode = $payment->party_code_snapshot ?? '';
        $partyPhone = $payment->party_phone_snapshot ?? '';
        $partyEmail = $payment->party_email_snapshot ?? '';

        if (! $partyName && $payment->party_id && $payment->party_type) {
            $table = $payment->party_type === 'customer' ? 'customers' : ($payment->party_type === 'supplier' ? 'suppliers' : null);
            if ($table) {
                $p = \Illuminate\Support\Facades\DB::table($table)->where('id', $payment->party_id)->first();
                if ($p) {
                    $partyName = $p->name ?? null;
                    $partyCode = $p->customer_number ?? $p->supplier_number ?? $p->code ?? '';
                    $partyPhone = $p->phone ?? '';
                    $partyEmail = $p->email ?? '';
                }
            }
        }

        return [
            'document_title' => $title,
            'party_role' => $partyRole,
            'is_inbound' => $isInbound,
            'payment_number' => $payment->payment_number,
            'payment_date' => $payment->payment_date->toDateString(),
            'created_at' => $payment->created_at?->toIso8601String() ?? now()->toIso8601String(),
            'status' => $payment->document_status instanceof \BackedEnum ? $payment->document_status->value : (string) $payment->document_status,
            'posting_status' => $payment->posting_status instanceof \BackedEnum ? $payment->posting_status->value : (string) $payment->posting_status,
            'party' => [
                'name' => $partyName ?? 'Valued Partner',
                'code' => $partyCode,
                'phone' => $partyPhone,
                'email' => $partyEmail,
            ],
            'currency' => [
                'code' => $curCode,
                'symbol' => $curSymbol,
            ],
            'agreement_reference' => $agreementRef,
            'agreement_references' => $agreementRefs,
            'reference_number' => $payment->reference_number,
            'notes' => $payment->notes,
            'total_amount' => (string) $payment->total_amount,
            'allocated_amount' => (string) $payment->allocated_amount,
            'unapplied_amount' => $unapplied,
            'amount_in_words' => $wordsText,
            'lines' => $lines,
            'allocations' => $allocations,
            'allocations_count' => count($allocations),
        ];
    }
}
