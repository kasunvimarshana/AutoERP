<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;

final class RentalDepositRequirementResource extends RentalResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'row_version' => (int) $this->row_version,
            'agreement' => $this->whenLoaded('agreement', fn () => $this->summary($this->agreement, ['agreement_number', 'agreement_kind', 'status'])),
            'required_amount' => $this->decimal($this->required_amount),
            'currency' => $this->whenLoaded('currency', fn () => $this->summary($this->currency, ['code', 'symbol'])),
            'due_date' => $this->due_date?->toDateString(),
            'is_refundable' => (bool) $this->is_refundable,
            'received_amount' => $this->decimal($this->received_amount),
            'applied_amount' => $this->decimal($this->applied_amount),
            'refunded_amount' => $this->decimal($this->refunded_amount),
            'balance_amount' => $this->decimal($this->balance_amount),
            'status' => $this->enumValue($this->status),
            'remarks' => $this->remarks,
            'links' => $this->loadedCollection('links', fn ($link): array => [
                'id' => (int) $link->getKey(), 'row_version' => (int) $link->row_version, 'link_type' => $this->enumValue($link->link_type),
                'payment' => $link->relationLoaded('payment')
                    ? $this->summary($link->payment, ['payment_number', 'row_version', 'document_status', 'posting_status', 'unapplied_amount'])
                    : null,
                'invoice' => $link->relationLoaded('invoice')
                    ? $this->summary($link->invoice, ['invoice_number', 'row_version', 'status', 'balance_due'])
                    : null,
                'reverses_link' => $link->relationLoaded('reversesLink') && $link->reversesLink !== null
                    ? [
                        'id' => (int) $link->reversesLink->getKey(),
                        'row_version' => (int) $link->reversesLink->row_version,
                        'link_type' => $this->enumValue($link->reversesLink->link_type),
                        'amount' => $this->decimal($link->reversesLink->amount),
                        'status' => $link->reversesLink->status,
                        'linked_at' => $link->reversesLink->linked_at?->toISOString(),
                    ]
                    : null,
                'amount' => $this->decimal($link->amount), 'status' => $link->status,
                'linked_at' => $link->linked_at?->toISOString(),
            ]),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
