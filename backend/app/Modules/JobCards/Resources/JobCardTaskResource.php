<?php

namespace App\Modules\JobCards\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class JobCardTaskResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'job_card_id' => $this->job_card_id,
            'sku_id' => $this->sku_id,
            'task_type' => $this->task_type,
            'description' => $this->description,
            'quantity' => number_format((float) $this->quantity, 2, '.', ''),
            'unit_price' => number_format((float) $this->unit_price, 2, '.', ''),
            'discount' => number_format((float) $this->discount, 2, '.', ''),
            'tax_rate' => number_format((float) $this->tax_rate, 2, '.', ''),
            'line_total' => number_format((float) $this->line_total, 2, '.', ''),
            'status' => $this->status,
            'completed_by' => $this->completed_by,
            'completed_at' => $this->completed_at ? $this->completed_at->toIso8601String() : null,
            'job_card' => $this->whenLoaded('jobCard', function () {
                return [
                    'id' => $this->jobCard->id,
                    'job_card_number' => $this->jobCard->job_card_number,
                    'status' => $this->jobCard->status,
                ];
            }),
            'sku' => $this->whenLoaded('sku', function () {
                return $this->sku ? [
                    'id' => $this->sku->id,
                    'sku_code' => $this->sku->sku_code,
                    'name' => $this->sku->name,
                ] : null;
            }),
            'completed_by_user' => $this->whenLoaded('completedBy', function () {
                return $this->completedBy ? [
                    'id' => $this->completedBy->id,
                    'name' => $this->completedBy->name,
                    'email' => $this->completedBy->email,
                ] : null;
            }),
            'tenant' => $this->whenLoaded('tenant', function () {
                return [
                    'id' => $this->tenant->id,
                    'name' => $this->tenant->name,
                    'domain' => $this->tenant->domain,
                ];
            }),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
