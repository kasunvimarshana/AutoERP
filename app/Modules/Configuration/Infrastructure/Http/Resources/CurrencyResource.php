<?php

declare(strict_types=1);

namespace Modules\Configuration\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrencyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'code' => $this->getCode(),
            'name' => $this->getName(),
            'symbol' => $this->getSymbol(),
            'decimal_places' => $this->getDecimalPlaces(),
            'is_active' => $this->isActive(),
            'created_at' => $this->getCreatedAt()?->toIso8601String(),
            'updated_at' => $this->getUpdatedAt()?->toIso8601String(),
        ];
    }
}
