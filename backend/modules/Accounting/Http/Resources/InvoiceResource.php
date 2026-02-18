<?php

namespace Modules\Accounting\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'customer_reference' => $this->when(
                $this->relationLoaded('customer'),
                fn() => [
                    'customer_id' => $this->customer_id,
                    'customer_name' => $this->customer?->name,
                    'customer_email' => $this->customer?->email,
                ]
            ),
            'sales_order_reference' => $this->sales_order_id,
            'invoice_status' => $this->status?->value,
            'status_label' => $this->getStatusLabel(),
            'date_information' => [
                'invoice_date' => $this->invoice_date->toDateString(),
                'due_date' => $this->due_date->toDateString(),
                'days_until_due' => $this->getDaysUntilDue(),
                'is_overdue' => $this->isOverdue(),
                'sent_timestamp' => $this->sent_at?->toIso8601String(),
                'paid_timestamp' => $this->paid_at?->toIso8601String(),
            ],
            'billing_information' => [
                'billing_address' => $this->billing_address,
            ],
            'financial_summary' => [
                'currency' => $this->currency_code,
                'subtotal_amount' => $this->formatMoney($this->subtotal),
                'tax_amount' => $this->formatMoney($this->tax_amount),
                'discount_amount' => $this->formatMoney($this->discount_amount),
                'total_amount' => $this->formatMoney($this->total_amount),
                'paid_amount' => $this->formatMoney($this->paid_amount),
                'outstanding_balance' => $this->formatMoney($this->balance_due),
                'payment_progress' => $this->calculatePaymentProgress(),
            ],
            'additional_information' => array_filter([
                'customer_notes' => $this->notes,
                'payment_terms' => $this->terms,
            ]),
            'line_items' => $this->when(
                $this->relationLoaded('lineItems'),
                fn() => InvoiceLineItemResource::collection($this->lineItems)
            ),
            'audit_trail' => [
                'created_by_user' => $this->created_by,
                'modified_by_user' => $this->updated_by,
                'created_at' => $this->created_at?->toIso8601String(),
                'updated_at' => $this->updated_at?->toIso8601String(),
            ],
        ];
    }

    private function getStatusLabel(): string
    {
        return match($this->status?->value) {
            'draft' => 'Draft Invoice',
            'sent' => 'Sent to Customer',
            'paid' => 'Fully Paid',
            'partially_paid' => 'Partially Paid',
            'overdue' => 'Payment Overdue',
            'cancelled' => 'Cancelled',
            default => 'Unknown Status',
        };
    }

    private function getDaysUntilDue(): int
    {
        return now()->diffInDays($this->due_date, false);
    }

    private function isOverdue(): bool
    {
        return $this->due_date->isPast() && $this->balance_due > 0;
    }

    private function calculatePaymentProgress(): float
    {
        if ($this->total_amount <= 0) {
            return 0;
        }

        return round(($this->paid_amount / $this->total_amount) * 100, 2);
    }

    private function formatMoney($amount): string
    {
        return number_format((float) $amount, 2, '.', ',');
    }
}
