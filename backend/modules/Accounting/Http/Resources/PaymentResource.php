<?php

namespace Modules\Accounting\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_number' => $this->payment_number,
            'customer_information' => $this->when(
                $this->relationLoaded('customer'),
                fn() => [
                    'customer_id' => $this->customer_id,
                    'customer_name' => $this->customer?->name,
                ]
            ),
            'payment_details' => [
                'payment_method' => $this->payment_method?->value,
                'method_label' => $this->getPaymentMethodLabel(),
                'payment_status' => $this->status?->value,
                'status_label' => $this->getStatusLabel(),
            ],
            'transaction_data' => [
                'payment_date' => $this->payment_date->toDateString(),
                'payment_timestamp' => $this->payment_date->toIso8601String(),
                'amount_paid' => $this->formatMoney($this->amount),
                'currency' => $this->currency_code,
            ],
            'reference_information' => array_filter([
                'external_reference' => $this->reference,
                'internal_notes' => $this->notes,
            ]),
            'allocations' => $this->when(
                $this->relationLoaded('allocations'),
                fn() => [
                    'total_allocations' => $this->allocations->count(),
                    'allocated_amount' => $this->formatMoney($this->allocations->sum('amount')),
                    'allocation_details' => PaymentAllocationResource::collection($this->allocations),
                ]
            ),
            'audit_metadata' => [
                'recorded_by' => $this->created_by,
                'last_modified_by' => $this->updated_by,
                'created_timestamp' => $this->created_at?->toIso8601String(),
                'modified_timestamp' => $this->updated_at?->toIso8601String(),
            ],
        ];
    }

    private function getPaymentMethodLabel(): string
    {
        return match($this->payment_method?->value) {
            'cash' => 'Cash Payment',
            'check' => 'Check Payment',
            'bank_transfer' => 'Bank Transfer',
            'credit_card' => 'Credit Card',
            'debit_card' => 'Debit Card',
            'online' => 'Online Payment',
            default => 'Other Method',
        };
    }

    private function getStatusLabel(): string
    {
        return match($this->status?->value) {
            'pending' => 'Pending Confirmation',
            'completed' => 'Payment Completed',
            'failed' => 'Payment Failed',
            'refunded' => 'Payment Refunded',
            default => 'Unknown Status',
        };
    }

    private function formatMoney($amount): string
    {
        return number_format((float) $amount, 2, '.', ',');
    }
}
