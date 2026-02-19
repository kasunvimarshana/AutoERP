<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\Helpers\MathHelper;
use Modules\CRM\Http\Resources\CustomerResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'organization_id' => $this->organization_id,
            'customer_id' => $this->customer_id,
            'order_id' => $this->order_id,
            'invoice_code' => $this->invoice_code,
            'reference' => $this->reference,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'invoice_date' => $this->invoice_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'discount_amount' => $this->discount_amount,
            'shipping_cost' => $this->shipping_cost,
            'total_amount' => $this->total_amount,
            'paid_amount' => $this->paid_amount,
            'balance_due' => MathHelper::subtract($this->total_amount, $this->paid_amount),
            'notes' => $this->notes,
            'terms_conditions' => $this->terms_conditions,
            'created_by' => $this->created_by,
            'sent_at' => $this->sent_at?->toISOString(),
            'paid_at' => $this->paid_at?->toISOString(),
            'overdue_at' => $this->overdue_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'cancellation_reason' => $this->cancellation_reason,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'payments' => InvoicePaymentResource::collection($this->whenLoaded('payments')),
            'items_count' => $this->when(
                $this->relationLoaded('items'),
                fn () => $this->items->count()
            ),
            'payments_count' => $this->when(
                $this->relationLoaded('payments'),
                fn () => $this->payments->count()
            ),
            'can_send' => $this->status->canSend(),
            'can_receive_payment' => $this->status->canReceivePayment(),
            'can_modify' => $this->status->canModify(),
            'is_paid' => $this->status->isPaid(),
            'is_overdue' => $this->status->isOverdue(),
            'is_final' => $this->status->isFinal(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}
