<?php

declare(strict_types=1);

namespace Modules\Core\Presentation\API\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\Domain\Aggregates\ResourceAggregate;

class CrudResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof ResourceAggregate) {
            return $this->resource->attributes();
        }

        if (is_array($this->resource)) {
            return $this->resource;
        }

        if (is_object($this->resource) && method_exists($this->resource, 'toArray')) {
            /** @var array<string, mixed> $data */
            $data = $this->resource->toArray();

            return $data;
        }

        return ['data' => $this->resource];
    }
}
