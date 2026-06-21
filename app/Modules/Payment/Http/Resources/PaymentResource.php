<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Payment\Services\PaymentCapabilityService;

final class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $capabilities = app(PaymentCapabilityService::class);

        return [
            'id' => (int) $this->getKey(),
            'tenant_id' => $this->tenant_id,
            'organization_unit_id' => $this->organization_unit_id,
            'payment_number' => $this->payment_number,
            'payment_type' => $this->enumValue($this->payment_type),
            'direction' => $this->enumValue($this->direction),
            'party_type' => $this->party_type,
            'party_id' => $this->party_id,
            'party' => $this->partySummary(),
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'document_status' => $this->enumValue($this->document_status),
            'allocation_status' => $this->enumValue($this->allocation_status),
            'posting_status' => $this->enumValue($this->posting_status),
            'instrument_status' => $this->enumValue($this->instrument_status),
            'status' => $this->enumValue($this->status),
            'payment_date' => $this->payment_date?->toDateString(),
            'currency_id' => $this->currency_id,
            'currency' => $this->whenLoaded('currency', fn () => $this->summary($this->currency, ['code', 'name', 'symbol'])),
            'exchange_rate' => (string) $this->exchange_rate,
            'reference_number' => $this->reference_number,
            'cheque_number' => $this->cheque_number,
            'cheque_date' => $this->cheque_date?->toDateString(),
            'bank_account_id' => $this->bank_account_id,
            'bank_account' => $this->whenLoaded('bankAccount', fn () => $this->summary($this->bankAccount, ['code', 'name'])),
            'payee_name' => $this->payee_name,
            'amount_in_words' => $this->amount_in_words,
            'total_amount' => (string) $this->total_amount,
            'allocated_amount' => (string) $this->allocated_amount,
            'unapplied_amount' => (string) $this->unapplied_amount,
            'refunded_amount' => (string) $this->refunded_amount,
            'finance_journal_entry_id' => $this->finance_journal_entry_id,
            'finance_journal' => $this->whenLoaded('financeJournalEntry', fn () => $this->summary($this->financeJournalEntry, ['journal_number', 'status'])),
            'posting_correlation_key' => $this->posting_correlation_key,
            'original_payment_id' => $this->original_payment_id,
            'original_payment' => $this->whenLoaded('originalPayment', fn () => $this->summary($this->originalPayment, ['payment_number', 'status'])),
            'reversal_payment_id' => $this->reversal_payment_id,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'created_by' => $this->created_by,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toISOString(),
            'posted_at' => $this->posted_at?->toISOString(),
            'voided_by' => $this->voided_by,
            'voided_at' => $this->voided_at?->toISOString(),
            'void_reason' => $this->void_reason,
            'reversed_by' => $this->reversed_by,
            'reversed_at' => $this->reversed_at?->toISOString(),
            'reversal_reason' => $this->reversal_reason,
            'capabilities' => $capabilities->capabilities($this->resource),
            'blockers' => $capabilities->blockers($this->resource),
            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(fn ($line): array => [
                'id' => (int) $line->getKey(),
                'payment_method_id' => $line->payment_method_id,
                'payment_method' => $line->relationLoaded('paymentMethod') && $line->paymentMethod !== null
                    ? [
                        'id' => (int) $line->paymentMethod->getKey(),
                        'code' => $line->paymentMethod->code,
                        'name' => $line->paymentMethod->name,
                        'method_type' => $this->enumValue($line->paymentMethod->method_type),
                        'requires_reference' => (bool) $line->paymentMethod->requires_reference,
                        'requires_bank_account' => (bool) $line->paymentMethod->requires_bank_account,
                    ]
                    : null,
                'reference_number' => $line->reference_number,
                'amount' => (string) $line->amount,
                'cleared_amount' => (string) $line->cleared_amount,
                'status' => (string) $line->status,
                'internal_bank_account_id' => $line->internal_bank_account_id,
                'instrument_direction' => $line->instrument_direction,
                'external_bank_name' => $line->external_bank_name,
                'external_bank_branch' => $line->external_bank_branch,
                'instrument_number' => $line->instrument_number,
                'instrument_date' => $line->instrument_date?->toDateString(),
                'deposit_date' => $line->deposit_date?->toDateString(),
                'realized_date' => $line->realized_date?->toDateString(),
                'clearing_date' => $line->clearing_date?->toDateString(),
                'bounced_date' => $line->bounced_date?->toDateString(),
                'returned_date' => $line->returned_date?->toDateString(),
                'notes' => $line->notes,
                'metadata' => $line->metadata,
            ])->values()->all()),
            'allocations' => $this->whenLoaded('allocations'),
            'unapplied_balance' => $this->whenLoaded('unappliedBalance'),
            'refunds' => $this->whenLoaded('refunds'),
            'reversals' => $this->whenLoaded('reversals'),
            'status_history' => $this->whenLoaded('statusHistory'),
        ];
    }

    private function enumValue(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }

    /** @param list<string> $fields */
    private function summary(?object $model, array $fields): ?array
    {
        if ($model === null) {
            return null;
        }

        $data = ['id' => method_exists($model, 'getKey') ? $model->getKey() : ($model->id ?? null)];
        foreach ($fields as $field) {
            $data[$field] = $this->enumValue($model->{$field} ?? null);
        }
        $data['name'] ??= $data['journal_number'] ?? $data['payment_number'] ?? $data['code'] ?? null;

        return $data;
    }

    private function partySummary(): ?array
    {
        if ($this->party_type === null || $this->party_id === null) {
            return null;
        }

        return [
            'id' => (int) $this->party_id,
            'type' => (string) $this->party_type,
            'name' => trim((string) $this->party_type).' #'.(int) $this->party_id,
        ];
    }
}
