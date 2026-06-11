<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Modules\Inventory\Contracts\ValuationMethodInterface;
use Modules\Inventory\DTOs\ValuationResultData;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\ValuationMethod;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\InventoryValuationConsumption;
use Modules\Inventory\Models\InventoryValuationLayer;
use Modules\Item\Enums\CostingMethod;
use Modules\Item\Models\Item;

final class InventoryValuationService
{
    public function __construct(
        private readonly Container $container,
        private readonly InventoryMethodResolver $methods,
    ) {}

    public function receive(InventoryMovement $movement): ValuationResultData
    {
        return $this->strategy($this->methodForMovement($movement))->receive($movement);
    }

    public function issue(InventoryMovement $movement, string $quantity): ValuationResultData
    {
        return $this->strategy($this->methodForMovement($movement))->issue($movement, $quantity);
    }

    public function reverse(InventoryMovement $movement, InventoryMovement $reversal): ValuationResultData
    {
        return $this->strategy($this->recordedMethod($movement))->reverse($movement, $reversal);
    }

    public function recalculate(InventoryMovement $movement): ValuationResultData
    {
        return $this->strategy($this->methodForMovement($movement))->recalculate($movement);
    }

    public function preview(InventoryMovement $movement, string $quantity): ValuationResultData
    {
        return $this->strategy($this->methodForMovement($movement))->preview($movement, $quantity);
    }

    public function methodFromItem(?string $costingMethod): ValuationMethod
    {
        return match ($costingMethod) {
            CostingMethod::WeightedAverage->value => ValuationMethod::WeightedAverage,
            CostingMethod::Standard->value, 'standard_cost' => ValuationMethod::Standard,
            CostingMethod::Manual->value, 'manual_cost' => ValuationMethod::Manual,
            default => ValuationMethod::FIFO,
        };
    }

    private function methodForMovement(InventoryMovement $movement): ValuationMethod
    {
        $movement->loadMissing('item.category');
        $item = $movement->item;
        if (! $item instanceof Item) {
            throw new InvalidArgumentException('Inventory movement item is required for valuation.');
        }

        return $this->methods->valuation(
            $item,
            (int) $movement->warehouse_id,
            $movement->organization_unit_id,
        );
    }

    private function recordedMethod(InventoryMovement $movement): ValuationMethod
    {
        if ($movement->direction === InventoryDirection::Out) {
            $consumption = InventoryValuationConsumption::query()
                ->where('issue_movement_id', $movement->getKey())
                ->with('valuationLayer')
                ->first();
            if ($consumption?->valuationLayer instanceof InventoryValuationLayer) {
                return $consumption->valuationLayer->valuation_method;
            }
        }

        $layer = InventoryValuationLayer::query()->where('movement_id', $movement->getKey())->first();

        return $layer instanceof InventoryValuationLayer
            ? $layer->valuation_method
            : $this->methodForMovement($movement);
    }

    private function strategy(ValuationMethod $method): ValuationMethodInterface
    {
        $class = config('inventory.valuation.strategies.'.$method->value);
        if (! is_string($class) || $class === '') {
            throw new InvalidArgumentException("Inventory valuation strategy [{$method->value}] is not configured.");
        }

        $strategy = $this->container->make($class);
        if (! $strategy instanceof ValuationMethodInterface) {
            throw new InvalidArgumentException("Inventory valuation strategy [{$class}] is invalid.");
        }

        return $strategy;
    }
}
