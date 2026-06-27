<?php

declare(strict_types=1);

namespace Modules\Vehicle\DTOs;

use Modules\Vehicle\Enums\VehicleOwnerType;

final readonly class VehicleOwnerSnapshot
{
    public function __construct(
        public VehicleOwnerType $type,
        public ?int $id,
        public string $code,
        public string $name,
    ) {}

    public function scopeKey(): string
    {
        return $this->id === null ? $this->type->value : $this->type->value.':'.$this->id;
    }
}
