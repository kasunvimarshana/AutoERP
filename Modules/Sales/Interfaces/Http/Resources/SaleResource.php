<?php
declare(strict_types=1);
namespace Modules\Sales\Interfaces\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class SaleResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id'             => $this->id,
            'invoice_number' => $this->invoice_number,
            'status'         => $this->status,
            'payment_status' => $this->payment_status,
            'subtotal'       => $this->subtotal,
            'discount_amount'=> $this->discount_amount,
            'tax_amount'     => $this->tax_amount,
            'total'          => $this->total,
            'paid_amount'    => $this->paid_amount,
            'due_amount'     => $this->due_amount,
            'sale_date'      => $this->sale_date?->toDateString(),
            'due_date'       => $this->due_date?->toDateString(),
            'lines'          => $this->whenLoaded('lines'),
            'payments'       => $this->whenLoaded('payments'),
            'tenant_id'      => $this->tenant_id,
            'created_at'     => $this->created_at?->toIso8601String(),
        ];
    }
}
