<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BaseResource extends JsonResource
{
    public function toArray($request)
    {
        // $resource = $this->resource;
        return parent::toArray($request);
    }

    public function with($request)
    {
        return [
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ];
    }
}
