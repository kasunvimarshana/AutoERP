<?php

declare(strict_types=1);

namespace Modules\UOM\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\Application\DTO\DataRecord;

final class UnitOfMeasureResource extends JsonResource
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
            'id'                   => $data['id'] ?? null,
            'tenant_id'            => $data['tenant_id'] ?? null,
            'organization_unit_id' => $data['organization_unit_id'] ?? null,
            'code'                 => $data['code'] ?? null,
            'name'                 => $data['name'] ?? null,
            'symbol'               => $data['symbol'] ?? null,
            'category'             => $data['category'] ?? null,
            'type'                 => $data['type'] ?? null,
            'decimal_precision'    => $data['decimal_precision'] ?? 0,
            'allow_fractional_quantity' => $data['allow_fractional_quantity'] ?? false,
            'is_base'              => $data['is_base'] ?? false,
            'usable_for_purchase'  => $data['usable_for_purchase'] ?? true,
            'usable_for_sales'     => $data['usable_for_sales'] ?? true,
            'usable_for_inventory' => $data['usable_for_inventory'] ?? true,
            'usable_for_service'   => $data['usable_for_service'] ?? true,
            'usable_for_rental'    => $data['usable_for_rental'] ?? false,
            'is_active'            => $data['is_active'] ?? true,
            'description'          => $data['description'] ?? null,
            'metadata'             => $data['metadata'] ?? null,
            'created_at'           => $data['created_at'] ?? null,
            'updated_at'           => $data['updated_at'] ?? null,
        ];
    }
}
