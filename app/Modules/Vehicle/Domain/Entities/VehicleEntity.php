<?php

declare(strict_types=1);

namespace Modules\Vehicle\Domain\Entities;

final class VehicleEntity
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(private readonly array $attributes)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }
}
