<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Entities;

final class CostCenterEntity
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
