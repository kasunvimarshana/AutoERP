<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\InventoryEngines;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\UseCases\InventoryEngines\ResolveInventoryDimensionsServiceInterface;
use Modules\Inventory\Domain\Constants\InventoryDimension;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;

final class ResolveInventoryDimensionsService implements ResolveInventoryDimensionsServiceInterface
{
    public function execute(array $payload): Result
    {
        try {
            $priority = config('inventory.engines.dimensions_priority', InventoryDimension::all());
            $knownDimensions = [];
            foreach ($priority as $key) {
                if (is_string($key) && $key !== '') {
                    $knownDimensions[] = $key;
                }
            }

            $inputDimensions = [];
            if (isset($payload['dimensions']) && is_array($payload['dimensions'])) {
                foreach ($payload['dimensions'] as $key => $value) {
                    if (is_string($key) && $key !== '') {
                        $inputDimensions[$key] = $value;
                    }
                }
            }

            $resolvedDimensions = [];
            foreach ($knownDimensions as $dimension) {
                $value = $payload[$dimension] ?? ($inputDimensions[$dimension] ?? null);
                if (is_string($value) && trim($value) === '') {
                    $value = null;
                }
                $resolvedDimensions[$dimension] = $value;
            }

            $extraDimensions = [];
            foreach ($inputDimensions as $key => $value) {
                if (! in_array($key, $knownDimensions, true)) {
                    $extraDimensions[$key] = $value;
                }
            }

            $specificityOrder = [];
            foreach ($knownDimensions as $dimension) {
                if (($resolvedDimensions[$dimension] ?? null) !== null) {
                    $specificityOrder[] = $dimension;
                }
            }

            return Result::success([
                'dimensions' => $resolvedDimensions,
                'extra_dimensions' => $extraDimensions,
                'specificity_order' => $specificityOrder,
                'transaction_type' => $payload['transaction_type'] ?? ($inputDimensions['transaction_type'] ?? null),
            ]);
        } catch (\Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
