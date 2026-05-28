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
            'name'                 => $data['name'] ?? null,
            'symbol'               => $data['symbol'] ?? null,
            'type'                 => $data['type'] ?? null,
            'is_base'              => $data['is_base'] ?? false,
            'metadata'             => $data['metadata'] ?? null,
            'created_at'           => $data['created_at'] ?? null,
            'updated_at'           => $data['updated_at'] ?? null,
        ];
    }
}
