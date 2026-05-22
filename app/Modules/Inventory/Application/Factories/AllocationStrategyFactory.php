<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Factories;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Modules\Inventory\Domain\Contracts\AllocationStrategyInterface;

final class AllocationStrategyFactory
{
    /**
     * @param array<string, class-string<AllocationStrategyInterface>> $strategies
     */
    public function __construct(
        private readonly Container $container,
        private readonly array $strategies,
    ) {
    }

    public function make(string $method): AllocationStrategyInterface
    {
        if (!isset($this->strategies[$method])) {
            throw new InvalidArgumentException("Unsupported allocation method: {$method}");
        }

        return $this->container->make($this->strategies[$method]);
    }
}
