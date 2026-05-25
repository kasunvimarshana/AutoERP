<?php

declare(strict_types=1);

namespace Modules\Configuration\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\Application\DTO\DataRecord;

final class CurrencyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof DataRecord) {
            return $this->resource->values;
        }

        if (is_array($this->resource)) {
            return $this->resource;
        }

        return [];
    }
}
