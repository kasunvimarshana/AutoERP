<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Repositories\StockLevelRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\UOM\Application\Contracts\Services\UomConversionServiceInterface;
use Throwable;

final class StockAvailabilityService
{
    public function __construct(
        private readonly StockLevelRepositoryInterface $stockLevelRepository,
        private readonly ItemRepositoryInterface $itemRepository,
        private readonly UomConversionServiceInterface $uomConversionService,
    ) {
    }

    public function forItem(int $tenantId, int $itemId, ?int $warehouseId = null, ?int $locationId = null): Result
    {
        return $this->check([
            'tenant_id' => $tenantId,
            'item_id' => $itemId,
            'warehouse_id' => $warehouseId,
            'location_id' => $locationId,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function check(array $payload): Result
    {
        try {
            $tenantId = isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : 0;
            $itemId = isset($payload['item_id']) ? (int) $payload['item_id'] : 0;

            if ($tenantId < 1 || $itemId < 1) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_VALUE,
                    'tenant_id and item_id are required.',
                ));
            }

            $criteria = $this->availabilityCriteria($payload, $tenantId, $itemId);
            $stockLevels = $this->stockLevelRepository->list($criteria);

            $quantityOnHand = 0.0;
            $reservedQuantity = 0.0;
            $blockedQuantity = 0.0;
            $damagedQuantity = 0.0;
            $inTransitQuantity = 0.0;

            foreach ($stockLevels as $stockLevel) {
                if (! $stockLevel instanceof DataRecord) {
                    continue;
                }

                $quantityOnHand += (float) $stockLevel->get('quantity_on_hand', 0);
                $reservedQuantity += (float) $stockLevel->get('quantity_reserved', 0);
                $blockedQuantity += (float) $stockLevel->get('quantity_blocked', 0);
                $damagedQuantity += (float) $stockLevel->get('quantity_damaged', 0);
                $inTransitQuantity += (float) $stockLevel->get('quantity_in_transit', 0);
            }

            $availableQuantity = round(
                max(0.0, $quantityOnHand - $reservedQuantity - $blockedQuantity - $damagedQuantity),
                4,
            );
            [$requestedQuantity, $baseRequestedQuantity, $warnings] = $this->requestedQuantity($payload);
            $decision = $baseRequestedQuantity === null || $availableQuantity >= $baseRequestedQuantity
                ? 'available'
                : 'insufficient';

            if ($quantityOnHand <= 0) {
                $warnings[] = 'No stock levels matched the selected dimensions.';
            }

            return Result::success([
                'tenant_id' => $tenantId,
                'item_id' => $itemId,
                'warehouse_id' => $criteria['warehouse_id'] ?? null,
                'location_id' => $criteria['location_id'] ?? null,
                'quantity_on_hand' => round($quantityOnHand, 4),
                'reserved_quantity' => round($reservedQuantity, 4),
                'blocked_quantity' => round($blockedQuantity, 4),
                'damaged_quantity' => round($damagedQuantity, 4),
                'in_transit_quantity' => round($inTransitQuantity, 4),
                'available_quantity' => $availableQuantity,
                'requested_quantity' => $requestedQuantity,
                'base_requested_quantity' => $baseRequestedQuantity,
                'decision' => $decision,
                'status' => $decision,
                'stock_levels' => $stockLevels,
                'breakdown' => [
                    ['label' => 'Requested in base UOM', 'value' => $this->formatQuantity($baseRequestedQuantity ?? 0.0)],
                    ['label' => 'On hand', 'value' => $this->formatQuantity($quantityOnHand)],
                    ['label' => 'Reserved', 'value' => $this->formatQuantity($reservedQuantity)],
                    ['label' => 'Blocked', 'value' => $this->formatQuantity($blockedQuantity)],
                    ['label' => 'Damaged', 'value' => $this->formatQuantity($damagedQuantity)],
                ],
                'calculated' => [
                    'requestedQuantity' => $this->formatQuantity($requestedQuantity ?? 0.0),
                    'baseRequestedQuantity' => $this->formatQuantity($baseRequestedQuantity ?? 0.0),
                    'availableQuantity' => $this->formatQuantity($availableQuantity),
                    'reservedQuantity' => $this->formatQuantity($reservedQuantity),
                    'requested_quantity' => $this->formatQuantity($requestedQuantity ?? 0.0),
                    'base_requested_quantity' => $this->formatQuantity($baseRequestedQuantity ?? 0.0),
                    'available_quantity' => $this->formatQuantity($availableQuantity),
                    'reserved_quantity' => $this->formatQuantity($reservedQuantity),
                    'decision' => $decision,
                    'status' => $decision,
                ],
                'errors' => $decision === 'available'
                    ? []
                    : ['Requested quantity exceeds available stock for the selected dimensions.'],
                'input' => $payload,
                'warnings' => array_values(array_unique($warnings)),
            ]);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function availabilityCriteria(array $payload, int $tenantId, int $itemId): array
    {
        $criteria = [
            'tenant_id' => $tenantId,
            'item_id' => $itemId,
        ];

        foreach (['organization_unit_id', 'warehouse_id', 'location_id', 'variant_id', 'batch_id', 'serial_id'] as $field) {
            if (array_key_exists($field, $payload) && $payload[$field] !== null && $payload[$field] !== '') {
                $criteria[$field] = (int) $payload[$field];
            }
        }

        return $criteria;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{0:?float,1:?float,2:array<int, string>}
     */
    private function requestedQuantity(array $payload): array
    {
        if (! isset($payload['quantity'])) {
            return [null, null, []];
        }

        $requestedQuantity = round((float) $payload['quantity'], 4);
        $baseRequestedQuantity = $requestedQuantity;
        $warnings = [];

        if (! isset($payload['uom_id'])) {
            return [$requestedQuantity, $baseRequestedQuantity, $warnings];
        }

        $tenantId = (int) $payload['tenant_id'];
        $itemId = (int) $payload['item_id'];
        $item = $this->itemRepository->findByIdInTenant($itemId, $tenantId);
        $baseUomId = $item instanceof DataRecord ? (int) $item->get('base_uom_id', 0) : 0;
        $requestUomId = (int) $payload['uom_id'];

        if ($baseUomId < 1 || $requestUomId < 1 || $baseUomId === $requestUomId) {
            return [$requestedQuantity, $baseRequestedQuantity, $warnings];
        }

        $conversion = $this->uomConversionService->convert(
            $requestedQuantity,
            $requestUomId,
            $baseUomId,
            $tenantId,
            $itemId,
        );

        if ($conversion->isFailure()) {
            throw new \InvalidArgumentException($conversion->errorOrFail()->message);
        }

        $baseRequestedQuantity = round((float) $conversion->valueOrFail(), 4);
        $warnings[] = 'Requested quantity was converted to the item base UOM before checking availability.';

        return [$requestedQuantity, $baseRequestedQuantity, $warnings];
    }

    private function formatQuantity(float $quantity): string
    {
        return number_format($quantity, 4, '.', '');
    }
}
