<?php

declare(strict_types=1);

namespace Modules\Selling\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Selling\Models\SaleReturn;

/** @mixin SaleReturn */
final class SaleReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'return_number' => $this->return_number,
            'return_date' => $this->return_date?->toDateString(),
            'reason' => $this->reason,
            'credit_amount' => (string) $this->credit_amount,
            'credit_allocated_amount' => (string) $this->credit_allocated_amount,
            'credit_available_amount' => (string) $this->credit_available_amount,
            'sale' => $this->whenLoaded('sale', fn (): array => [
                'id' => (int) $this->sale->getKey(),
                'sale_number' => $this->sale->sale_number,
                'customer' => $this->sale->customer?->display_name ?: $this->sale->customer?->name,
                'invoice' => $this->sale->invoice === null ? null : [
                    'id' => (int) $this->sale->invoice->getKey(),
                    'number' => $this->sale->invoice->invoice_number,
                    'balance_due' => (string) $this->sale->invoice->balance_due,
                ],
            ]),
            'lines' => $this->whenLoaded('lines', fn (): array => $this->lines->map(static fn ($line): array => [
                'item' => $line->saleLine->description,
                'quantity' => (string) $line->quantity,
                'credit_amount' => (string) $line->credit_amount,
            ])->all()),
        ];
    }
}
