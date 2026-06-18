<?php

declare(strict_types=1);

namespace Modules\Warehouse\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\DTOs\DataRecord;

final class WarehouseLocationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof DataRecord) {
            return $this->resource->toArray();
        }

        if (is_array($this->resource)) {
            return $this->resource;
        }

        if ($this->resource instanceof Model) {
            return [
                'id' => (int) $this->resource->getKey(),
                'row_version' => (int) $this->resource->row_version,
                'warehouse' => $this->whenLoaded('warehouse', fn () => $this->summary($this->resource->warehouse)),
                'parent' => $this->whenLoaded('parent', fn () => $this->summary($this->resource->parent)),
                'organization_unit' => $this->whenLoaded('organizationUnit', fn () => $this->summary($this->resource->organizationUnit)),
                'code' => $this->resource->code,
                'name' => $this->resource->name,
                'path' => $this->resource->path,
                'depth' => (int) $this->resource->depth,
                'type' => $this->resource->type,
                'type_label' => $this->typeLabel((string) $this->resource->type),
                'capacity' => $this->resource->capacity === null ? null : (string) $this->resource->capacity,
                'is_default' => (bool) $this->resource->is_default,
                'is_pickable' => (bool) $this->resource->is_pickable,
                'is_receivable' => (bool) $this->resource->is_receivable,
                'is_active' => (bool) $this->resource->is_active,
                'metadata' => $this->resource->metadata,
                'created_at' => $this->resource->created_at?->toISOString(),
                'updated_at' => $this->resource->updated_at?->toISOString(),
            ];
        }

        return [];
    }

    private function typeLabel(string $type): string
    {
        return [
            'zone' => 'Zone',
            'aisle' => 'Aisle',
            'rack' => 'Rack',
            'shelf' => 'Shelf',
            'bin' => 'Bin',
            'staging' => 'Staging',
            'dispatch' => 'Dispatch',
        ][$type] ?? ucfirst($type);
    }

    private function summary(?Model $model): ?array
    {
        if (! $model instanceof Model) {
            return null;
        }

        return [
            'id' => (int) $model->getKey(),
            'code' => $model->getAttribute('code'),
            'name' => $model->getAttribute('name'),
        ];
    }
}
