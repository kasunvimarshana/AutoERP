<?php

declare(strict_types=1);

namespace Modules\Sales\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\Application\DTO\DataRecord;

final class SalesOrderLineResource extends JsonResource
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

        return [];
    }
}