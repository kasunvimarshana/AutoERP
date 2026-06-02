<?php

namespace Modules\Document\Presentation\API\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Document\Domain\Entities\DocumentItem;

class DocumentItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof DocumentItem) {
            $data = $this->resource->data;

            return [
                'id' => $this->resource->id,
                'document_id' => $this->resource->documentId,
                'line_no' => $this->resource->sequence,
                'line_type' => $this->resource->itemType,
                'item_type' => $this->resource->itemType,
                'item_label' => $data['item_label'] ?? $data['label'] ?? null,
                'description' => $this->resource->description,
                'quantity' => $data['quantity'] ?? null,
                'uom_label' => $data['uom_label'] ?? $data['uom'] ?? null,
                'unit_price' => $data['unit_price'] ?? null,
                'discount_amount' => $data['discount_amount'] ?? null,
                'tax_amount' => $data['tax_amount'] ?? null,
                'line_total' => $this->resource->lineTotal,
                'source_line_type' => $data['source_line_type'] ?? null,
                'source_line_id' => $data['source_line_id'] ?? null,
                'display_order' => $this->resource->sequence,
                'sequence' => $this->resource->sequence,
                'data' => $data,
            ];
        }

        return [
            'id' => $this->id,
            'document_id' => $this->document_id,
            'line_no' => $this->line_no ?? $this->sequence,
            'line_type' => $this->line_type ?? $this->item_type,
            'item_type' => $this->item_type,
            'item_label' => $this->item_label,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'uom_label' => $this->uom_label,
            'unit_price' => $this->unit_price,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'line_total' => $this->line_total,
            'source_line_type' => $this->source_line_type,
            'source_line_id' => $this->source_line_id,
            'display_order' => $this->display_order ?? $this->sequence,
            'sequence' => $this->sequence,
            'data' => $this->data ?? [],
        ];
    }
}
