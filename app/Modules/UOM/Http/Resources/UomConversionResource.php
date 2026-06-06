<?php

declare(strict_types=1);

namespace Modules\UOM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\DTOs\DataRecord;

final class UomConversionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = $this->resource instanceof DataRecord
            ? $this->resource->toArray()
            : (is_array($this->resource) ? $this->resource : []);

        return [
            'id' => $data['id'] ?? null,
            'tenant_id' => $data['tenant_id'] ?? null,
            'organization_unit_id' => $data['organization_unit_id'] ?? null,
            'from_uom_id' => $data['from_uom_id'] ?? null,
            'to_uom_id' => $data['to_uom_id'] ?? null,
            'factor' => $data['factor'] ?? null,
            'category' => $data['category'] ?? null,
            'item_id' => $data['item_id'] ?? null,
            'is_bidirectional' => $data['is_bidirectional'] ?? true,
            'is_active' => $data['is_active'] ?? true,
            'effective_from' => $data['effective_from'] ?? null,
            'effective_to' => $data['effective_to'] ?? null,
            'notes' => $data['notes'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'created_at' => $data['created_at'] ?? null,
            'updated_at' => $data['updated_at'] ?? null,
        ];
    }
}
