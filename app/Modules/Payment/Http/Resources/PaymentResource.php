<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Payment\Services\PaymentCapabilityService;

final class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $capabilities = app(PaymentCapabilityService::class);

        return [
            'id' => (int) $this->getKey(),
            'row_version' => (int) $this->row_version,
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
            'payment_date' => $this->payment_date?->toDateString(),
            'currency_id' => $this->currency_id,
            'currency' => $this->currencySummary(),
            'exchange_rate' => (string) $this->exchange_rate,
            'reference_number' => $this->reference_number,
            'cheque_number' => $this->cheque_number,
            'cheque_date' => $this->cheque_date?->toDateString(),
            'payee_name' => $this->payee_name,
            'amount_in_words' => $this->amount_in_words,
            'total_amount' => (string) $this->total_amount,
            'allocated_amount' => (string) $this->allocated_amount,
            'unapplied_amount' => (string) $this->unapplied_amount,
            'refunded_amount' => (string) $this->refunded_amount,
            'finance_posting_reference' => $this->finance_posting_reference,
            'posting_correlation_key' => $this->posting_correlation_key,
            'original_payment_id' => $this->original_payment_id,
            'original_payment' => $this->whenLoaded('originalPayment', fn () => $this->summary($this->originalPayment, ['payment_number', 'document_status'])),
            'reversal_payment_id' => $this->reversal_payment_id,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toISOString(),
            'posted_by' => $this->posted_by,
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
                'row_version' => (int) $line->row_version,
                'line_number' => (int) $line->line_number,
                'payment_method_id' => $line->payment_method_id,
                'payment_method' => [
                    'id' => (int) $line->payment_method_id,
                    'code' => $line->payment_method_code_snapshot,
                    'name' => $line->payment_method_name_snapshot,
                    'method_type' => $line->payment_method_type_snapshot,
                    'requires_reference' => (bool) $line->requires_reference_snapshot,
                    'requires_instrument_details' => (bool) $line->requires_instrument_details_snapshot,
                ],
                'reference_number' => $line->reference_number,
                'amount' => (string) $line->amount,
                'cleared_amount' => (string) $line->cleared_amount,
                'status' => (string) $line->status,
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
            ])->values()->all()),
            'allocations' => $this->whenLoaded('allocations'),
            'unapplied_balance' => $this->whenLoaded('unappliedBalance'),
            'refunds' => $this->whenLoaded('refunds'),
            'reversals' => $this->whenLoaded('reversals'),
            'lifecycle_events' => $this->whenLoaded('lifecycleEvents'),
        ];
    }

    private function enumValue(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }

    private function summary(?object $model, array $fields): ?array
    {
        if ($model === null) {
            return null;
        }

        $data = ['id' => method_exists($model, 'getKey') ? $model->getKey() : ($model->id ?? null)];
        foreach ($fields as $field) {
            $data[$field] = $this->enumValue($model->{$field} ?? null);
        }
        $data['name'] ??= $data['payment_number'] ?? $data['code'] ?? null;

        return $data;
    }

    private function partySummary(): ?array
    {
        if ($this->party_type === null && $this->party_id === null) {
            return null;
        }

        return [
            'id' => $this->party_id === null ? null : (int) $this->party_id,
            'type' => $this->party_type,
            'number' => $this->party_number_snapshot,
            'code' => $this->party_code_snapshot,
            'name' => $this->party_name_snapshot,
            'email' => $this->party_email_snapshot,
            'phone' => $this->party_phone_snapshot,
        ];
    }

    private function currencySummary(): ?array
    {
        if ($this->currency_id === null && $this->currency_code_snapshot === null) {
            return null;
        }

        return [
            'id' => $this->currency_id === null ? null : (int) $this->currency_id,
            'code' => $this->currency_code_snapshot,
            'name' => $this->currency_name_snapshot,
            'symbol' => $this->currency_symbol_snapshot,
        ];
    }
}
