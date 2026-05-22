<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Factories;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Modules\Inventory\Domain\Contracts\ValuationStrategyInterface;

final class ValuationStrategyFactory
{
    /**
     * @param array<string, class-string<ValuationStrategyInterface>> $strategies
     */
    public function __construct(
        private readonly Container $container,
        private readonly array $strategies,
    ) {
    }

    public function make(string $method): ValuationStrategyInterface
    {
        if (!isset($this->strategies[$method])) {
            throw new InvalidArgumentException("Unsupported valuation method: {$method}");
        }

        return $this->container->make($this->strategies[$method]);
    }
}
