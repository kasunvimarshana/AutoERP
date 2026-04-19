<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimezoneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'offset' => $this->getOffset(),
            'created_at' => $this->getCreatedAt()?->toIso8601String(),
            'updated_at' => $this->getUpdatedAt()?->toIso8601String(),
        ];
    }
}
