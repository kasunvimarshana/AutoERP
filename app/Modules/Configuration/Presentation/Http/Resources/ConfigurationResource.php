<?php

declare(strict_types=1);

namespace Modules\Configuration\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Configuration\Application\DTOs\ConfigurationValueData;

/**
 * @mixin ConfigurationValueData
 */
final class ConfigurationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof ConfigurationValueData) {
            return $this->resource->toArray();
        }

        if (is_array($this->resource)) {
            return $this->resource;
        }

        return [
            'key' => null,
            'value' => null,
            'source' => null,
            'description' => null,
            'updated_at' => null,
            'scope' => null,
            'tenant_id' => null,
            'organization_unit_id' => null,
            'resolved_from' => null,
        ];
    }
}
