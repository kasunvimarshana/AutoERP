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
                'id' => (int) $link->getKey(), 'link_type' => $this->enumValue($link->link_type),
                'payment_id' => $link->payment_id, 'invoice_id' => $link->invoice_id,
                'amount' => $this->decimal($link->amount), 'status' => $link->status,
                'linked_at' => $link->linked_at?->toISOString(), 'reverses_link_id' => $link->reverses_link_id,
            ]),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
