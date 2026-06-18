<?php

declare(strict_types=1);

namespace Modules\Warehouse\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\DTOs\DataRecord;

final class WarehouseResource extends JsonResource
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
                'code' => $this->resource->code,
                'name' => $this->resource->name,
                'type' => $this->resource->type,
                'type_label' => $this->typeLabel((string) $this->resource->type),
                'organization_unit' => $this->whenLoaded('organizationUnit', fn () => $this->summary($this->resource->organizationUnit)),
                'is_default' => (bool) $this->resource->is_default,
                'is_active' => (bool) $this->resource->is_active,
                'locations_count' => (int) ($this->resource->locations_count ?? 0),
                'default_location' => $this->whenLoaded('defaultLocation', fn () => $this->summary($this->resource->defaultLocation)),
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
            'standard' => 'Standard',
            'virtual' => 'Virtual',
            'transit' => 'Transit',
            'quarantine' => 'Quarantine',
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
