<?php

declare(strict_types=1);

namespace App\Modules\POS\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class POSSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'session_number' => $this->session_number,
            'terminal_id' => $this->terminal_id,
            'cashier_id' => $this->cashier_id,
            'opened_at' => $this->opened_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'opening_cash' => $this->opening_cash,
            'closing_cash' => $this->closing_cash,
            'expected_cash' => $this->expected_cash,
            'cash_difference' => $this->cash_difference,
            'total_sales' => $this->total_sales,
            'total_returns' => $this->total_returns,
            'transaction_count' => $this->transaction_count,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'cashier' => $this->when($this->relationLoaded('cashier'), fn() => [
                'id' => $this->cashier->id,
                'name' => $this->cashier->name,
            ]),
        ];
    }
}
