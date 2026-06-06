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
            'from_uom' => $this->uomSummary($data['from_uom'] ?? null, $data['from_uom_id'] ?? null),
            'to_uom' => $this->uomSummary($data['to_uom'] ?? null, $data['to_uom_id'] ?? null),
            'conversion_factor' => isset($data['conversion_factor'])
                ? rtrim(rtrim((string) $data['conversion_factor'], '0'), '.')
                : null,
            'is_active' => $data['is_active'] ?? true,
            'description' => $data['description'] ?? null,
            'created_at' => $data['created_at'] ?? null,
            'updated_at' => $data['updated_at'] ?? null,
        ];
    }

    private function uomSummary(mixed $uom, mixed $fallbackId): ?array
    {
        if (! is_array($uom)) {
            return $fallbackId === null ? null : [
                'id' => $fallbackId,
                'code' => null,
                'name' => null,
                'symbol' => null,
            ];
        }

        return [
            'id' => $uom['id'] ?? $fallbackId,
            'code' => $uom['code'] ?? null,
            'name' => $uom['name'] ?? null,
            'symbol' => $uom['symbol'] ?? null,
        ];
    }
}
