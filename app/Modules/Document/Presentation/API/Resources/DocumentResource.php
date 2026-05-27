<?php

namespace Modules\Document\Presentation\API\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Document\Domain\Aggregates\DocumentAggregate;

class DocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof DocumentAggregate) {
            return [
                'id' => $this->resource->document->id,
                'tenant_id' => $this->resource->document->tenantId,
                'document_type_id' => $this->resource->document->documentTypeId,
                'document_number' => $this->resource->document->documentNumber,
                'status' => $this->resource->document->status,
                'document_date' => $this->resource->document->documentDate,
                'due_date' => $this->resource->document->dueDate,
                'subtotal' => $this->resource->document->subtotal,
                'discount_total' => $this->resource->document->discountTotal,
                'tax_total' => $this->resource->document->taxTotal,
                'grand_total' => $this->resource->document->grandTotal,
                'data' => $this->resource->document->data,
                'notes' => $this->resource->document->notes,
                'attachments' => $this->resource->document->attachments,
                'items' => DocumentItemResource::collection($this->resource->items),
            ];
        }

        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'document_type_id' => $this->document_type_id,
            'document_number' => $this->document_number,
            'status' => $this->status,
            'document_date' => optional($this->document_date)->format('Y-m-d'),
            'due_date' => optional($this->due_date)->format('Y-m-d'),
            'subtotal' => $this->subtotal,
            'discount_total' => $this->discount_total,
            'tax_total' => $this->tax_total,
            'grand_total' => $this->grand_total,
            'data' => $this->data ?? [],
            'notes' => $this->notes,
            'attachments' => $this->attachments ?? [],
            'items' => DocumentItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
