<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\Helpers\MathHelper;

class BillResource extends JsonResource
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
            'vendor_id' => $this->vendor_id,
            'purchase_order_id' => $this->purchase_order_id,
            'goods_receipt_id' => $this->goods_receipt_id,
            'bill_code' => $this->bill_code,
            'vendor_invoice_number' => $this->vendor_invoice_number,
            'reference' => $this->reference,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'bill_date' => $this->bill_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'total_amount' => $this->total_amount,
            'paid_amount' => $this->paid_amount,
            'remaining_balance' => MathHelper::subtract($this->total_amount, $this->paid_amount),
            'notes' => $this->notes,
            'terms_conditions' => $this->terms_conditions,
            'created_by' => $this->created_by,
            'sent_at' => $this->sent_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'cancellation_reason' => $this->cancellation_reason,
            'vendor' => new VendorResource($this->whenLoaded('vendor')),
            'purchase_order' => $this->when(
                $this->relationLoaded('purchaseOrder'),
                fn () => [
                    'id' => $this->purchaseOrder->id,
                    'po_code' => $this->purchaseOrder->po_code,
                    'status' => $this->purchaseOrder->status->value,
                ]
            ),
            'goods_receipt' => $this->when(
                $this->relationLoaded('goodsReceipt'),
                fn () => [
                    'id' => $this->goodsReceipt->id,
                    'gr_code' => $this->goodsReceipt->gr_code,
                    'status' => $this->goodsReceipt->status->value,
                ]
            ),
            'items' => BillItemResource::collection($this->whenLoaded('items')),
            'payments' => BillPaymentResource::collection($this->whenLoaded('payments')),
            'items_count' => $this->when(
                $this->relationLoaded('items'),
                fn () => $this->items->count()
            ),
            'payments_count' => $this->when(
                $this->relationLoaded('payments'),
                fn () => $this->payments->count()
            ),
            'can_modify' => $this->status->canModify(),
            'can_send' => $this->status->canSend(),
            'can_pay' => $this->status->canPay(),
            'can_cancel' => $this->status->canCancel(),
            'is_overdue' => $this->isOverdue(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}
