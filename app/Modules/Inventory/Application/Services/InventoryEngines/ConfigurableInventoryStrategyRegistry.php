<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services\InventoryEngines;

use Illuminate\Contracts\Container\Container;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\InventoryEngines\InventoryStrategyRegistryInterface;
use Modules\Inventory\Application\Contracts\Strategies\AllocationStrategyInterface;
use Modules\Inventory\Application\Contracts\Strategies\ValuationStrategyInterface;
use Modules\Inventory\Domain\Constants\InventoryEngineKey;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class ConfigurableInventoryStrategyRegistry implements InventoryStrategyRegistryInterface
{
    public function __construct(private readonly Container $container) {}

    public function resolveValuation(string $method): Result
    {
        return $this->resolve(
            InventoryEngineKey::VALUATION,
            $method,
            ValuationStrategyInterface::class,
            'valuation',
        );
    }

    public function resolveAllocation(string $method): Result
    {
        return $this->resolve(
            InventoryEngineKey::ALLOCATION,
            $method,
            AllocationStrategyInterface::class,
            'allocation',
        );
    }

    public function valuationMethods(): array
    {
        return $this->registeredMethods(InventoryEngineKey::VALUATION);
    }

    public function allocationMethods(): array
    {
        return $this->registeredMethods(InventoryEngineKey::ALLOCATION);
    }

    private function resolve(string $engine, string $method, string $contract, string $label): Result
    {
        try {
            $normalizedMethod = $this->normalizeMethod($method);
            if ($normalizedMethod === '') {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_STRATEGY,
                    sprintf('A %s method is required.', $label),
                ));
            }

            $strategies = $this->strategyMap($engine);
            $definition = $strategies[$normalizedMethod] ?? null;

            if ($definition === null) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_STRATEGY,
                    sprintf('Unsupported %s method: %s', $label, $method),
                    [
                        'method' => $method,
                        'registered_methods' => $this->registeredMethods($engine),
                    ],
                ));
            }

            [$strategyClass, $parameters] = $this->strategyDefinition($definition, $normalizedMethod);
            if ($strategyClass === null) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_STRATEGY,
                    sprintf('The %s strategy definition is invalid for method: %s', $label, $normalizedMethod),
                ));
            }

            $strategy = $this->container->make($strategyClass, $parameters);
            if (! $strategy instanceof $contract) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_STRATEGY,
                    sprintf('Configured %s strategy does not implement the required contract: %s', $label, $strategyClass),
                ));
            }

            return Result::success($strategy);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_STRATEGY, $exception->getMessage()));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function strategyMap(string $engine): array
    {
        $legacy = (array) config(sprintf('inventory.engines.%s_strategy_map', $engine), []);
        $configured = (array) config(sprintf('inventory.engines.%s.strategy_map', $engine), []);

        return array_replace($legacy, $configured);
    }

    /**
     * @return list<string>
     */
    private function registeredMethods(string $engine): array
    {
        $methods = [];
        foreach (array_keys($this->strategyMap($engine)) as $method) {
            if (is_string($method) && trim($method) !== '') {
                $methods[] = $this->normalizeMethod($method);
            }
        }

        sort($methods);

        return array_values(array_unique($methods));
    }

    /**
     * @return array{0: class-string|null, 1: array<string, mixed>}
     */
    private function strategyDefinition(mixed $definition, string $method): array
    {
        if (is_string($definition) && trim($definition) !== '') {
            return [$definition, []];
        }

        if (! is_array($definition)) {
            return [null, []];
        }

        $class = $definition['class'] ?? $definition['strategy'] ?? null;
        $parameters = $definition['parameters'] ?? $definition['arguments'] ?? [];

        if (! is_string($class) || trim($class) === '') {
            return [null, []];
        }

        if (! is_array($parameters)) {
            $parameters = [];
        }

        $parameters['method'] ??= $method;

        return [$class, $parameters];
    }

    private function normalizeMethod(string $method): string
    {
        return strtolower(trim($method));
    }
}
