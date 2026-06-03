<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services\InventoryEngines;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\UseCases\InventoryEngines\ResolveInventoryDimensionsServiceInterface;
use Modules\Inventory\Domain\Constants\InventoryDimension;
use Modules\Inventory\Domain\Constants\InventoryEngineKey;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class InventoryEngineContextFactory
{
    public function __construct(
        private readonly ResolveInventoryDimensionsServiceInterface $dimensionResolver,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(string $engine, array $payload): Result
    {
        try {
            $quantityField = $this->stringConfig('inventory.engines.quantity_field', 'quantity');
            $requestedQuantity = (float) ($this->payloadValue($payload, $quantityField) ?? 0);

            if ($requestedQuantity <= 0.0) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_VALUE,
                    sprintf('%s (> 0) is required for inventory %s.', $quantityField, $engine),
                ));
            }

            $missing = $this->missingRequiredDimensions($payload);
            if ($missing !== []) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_VALUE,
                    sprintf('%s are required for inventory %s.', implode(', ', $missing), $engine),
                    ['missing_fields' => $missing],
                ));
            }

            $resolvedDimensionsResult = $this->dimensionResolver->execute($payload);
            if ($resolvedDimensionsResult->isFailure()) {
                return $resolvedDimensionsResult;
            }

            $resolved = (array) $resolvedDimensionsResult->valueOrFail();
            $dimensions = (array) ($resolved[InventoryEngineKey::DIMENSIONS] ?? []);

            return Result::success([
                'engine' => $engine,
                InventoryEngineKey::PAYLOAD => $payload,
                InventoryEngineKey::REQUESTED_QUANTITY => $requestedQuantity,
                InventoryEngineKey::DIMENSIONS => $dimensions,
                InventoryEngineKey::EXTRA_DIMENSIONS => (array) ($resolved[InventoryEngineKey::EXTRA_DIMENSIONS] ?? []),
                InventoryEngineKey::SPECIFICITY_ORDER => (array) ($resolved[InventoryEngineKey::SPECIFICITY_ORDER] ?? []),
                InventoryEngineKey::TRANSACTION_TYPE => $resolved[InventoryEngineKey::TRANSACTION_TYPE] ?? null,
                'precision' => $this->integerConfig('inventory.engines.precision', 4),
            ]);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  list<string>|null  $fields
     * @return array<string, mixed>
     */
    public function criteria(array $context, ?array $fields = null, bool $includeNulls = false): array
    {
        $payload = (array) ($context[InventoryEngineKey::PAYLOAD] ?? []);
        $dimensions = (array) ($context[InventoryEngineKey::DIMENSIONS] ?? []);
        $fields ??= $this->dimensionFields('inventory.engines.criteria_dimensions');

        $criteria = [];
        foreach ($fields as $field) {
            if (! is_string($field) || trim($field) === '') {
                continue;
            }

            $value = $dimensions[$field] ?? $this->payloadValue($payload, $field);
            if (is_string($value) && trim($value) === '') {
                $value = null;
            }

            if ($value === null && ! $includeNulls) {
                continue;
            }

            $criteria[$field] = $value;
        }

        return $criteria;
    }

    /**
     * @return list<string>
     */
    public function dimensionFields(string $configKey): array
    {
        $configured = config($configKey, InventoryDimension::all());
        if (! is_array($configured)) {
            return InventoryDimension::all();
        }

        $fields = [];
        foreach ($configured as $field) {
            if (is_string($field) && trim($field) !== '') {
                $fields[] = $field;
            }
        }

        return $fields === [] ? InventoryDimension::all() : array_values(array_unique($fields));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function requestedMethod(string $engine, array $payload, ?string $configuredMethod = null): string
    {
        $field = $this->stringConfig(sprintf('inventory.engines.%s.method_field', $engine), sprintf('%s_method', $engine));
        $legacyDefaultKey = sprintf('inventory.engines.default_%s_method', $engine);
        $default = $this->stringConfig(
            sprintf('inventory.engines.%s.default_method', $engine),
            $this->stringConfig($legacyDefaultKey, ''),
        );

        $method = $this->payloadValue($payload, $field) ?? $configuredMethod ?? $default;

        return strtolower(trim((string) $method));
    }

    /**
     * @return array<string, mixed>
     */
    public function methodOptions(string $engine, string $method): array
    {
        return (array) config(sprintf('inventory.engines.%s.methods.%s', $engine, strtolower(trim($method))), []);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    private function missingRequiredDimensions(array $payload): array
    {
        $required = config('inventory.engines.required_dimensions', [
            InventoryDimension::TENANT_ID,
            InventoryDimension::ITEM_ID,
        ]);

        if (! is_array($required)) {
            return [];
        }

        $missing = [];
        foreach ($required as $field) {
            if (! is_string($field) || trim($field) === '') {
                continue;
            }

            $value = $this->payloadValue($payload, $field);
            if ($value === null || $value === '') {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function payloadValue(array $payload, string $field): mixed
    {
        if (array_key_exists($field, $payload)) {
            return $payload[$field];
        }

        $dimensions = $payload[InventoryEngineKey::DIMENSIONS] ?? null;
        if (is_array($dimensions) && array_key_exists($field, $dimensions)) {
            return $dimensions[$field];
        }

        return null;
    }

    private function stringConfig(string $key, string $default): string
    {
        $value = config($key, $default);

        return is_string($value) && trim($value) !== '' ? $value : $default;
    }

    private function integerConfig(string $key, int $default): int
    {
        $value = config($key, $default);

        return is_int($value) && $value > 0 ? $value : $default;
    }
}
