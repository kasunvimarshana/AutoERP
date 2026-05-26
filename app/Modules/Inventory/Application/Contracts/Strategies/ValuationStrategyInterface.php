<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\Strategies;

interface ValuationStrategyInterface
{
    public function method(): string;

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function calculate(array $context): array;
}
