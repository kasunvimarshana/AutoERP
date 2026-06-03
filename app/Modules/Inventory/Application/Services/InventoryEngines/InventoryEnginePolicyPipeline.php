<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services\InventoryEngines;

use Illuminate\Contracts\Container\Container;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\InventoryEngines\InventoryEnginePolicyInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class InventoryEnginePolicyPipeline
{
    public function __construct(private readonly Container $container) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function inspect(string $engine, array $context): Result
    {
        try {
            foreach ($this->policies($engine) as $definition) {
                [$policyClass, $parameters] = $this->policyDefinition($definition);
                if ($policyClass === null) {
                    return Result::failure(new Error(
                        InventoryErrorCode::INVALID_VALUE,
                        sprintf('Invalid inventory %s policy configuration.', $engine),
                    ));
                }

                $policy = $this->container->make($policyClass, $parameters);
                if (! $policy instanceof InventoryEnginePolicyInterface) {
                    return Result::failure(new Error(
                        InventoryErrorCode::INVALID_VALUE,
                        sprintf('Inventory policy must implement %s: %s', InventoryEnginePolicyInterface::class, $policyClass),
                    ));
                }

                $result = $policy->inspect($context);
                if ($result->isFailure()) {
                    return $result;
                }
            }

            return Result::success(true);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    /**
     * @return list<mixed>
     */
    private function policies(string $engine): array
    {
        $configured = config(sprintf('inventory.engines.%s.policies', $engine), []);

        return is_array($configured) ? array_values($configured) : [];
    }

    /**
     * @return array{0: class-string|null, 1: array<string, mixed>}
     */
    private function policyDefinition(mixed $definition): array
    {
        if (is_string($definition) && trim($definition) !== '') {
            return [$definition, []];
        }

        if (! is_array($definition)) {
            return [null, []];
        }

        $class = $definition['class'] ?? $definition['policy'] ?? null;
        $parameters = $definition['parameters'] ?? $definition['arguments'] ?? [];

        if (! is_string($class) || trim($class) === '') {
            return [null, []];
        }

        return [$class, is_array($parameters) ? $parameters : []];
    }
}
