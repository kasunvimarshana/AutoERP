<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\Strategies;

interface AllocationStrategyInterface
{
    public function method(): string;

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function allocate(array $context): array;
}
