<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

/**
 * Data Transfer Object for creating or updating a stock batch record.
 *
 * A stock batch is a uniquely identified lot or batch of a product held in a
 * specific warehouse location. Quantity and cost values are typed as string
 * to enforce BCMath arithmetic throughout the system.
 */
final class StockBatchDTO
{
    public function __construct(
        public readonly int     $warehouseId,
        public readonly int     $productId,
        public readonly int     $uomId,
        public readonly string  $quantity,
        public readonly string  $costPrice,
        public readonly ?string $batchNumber,
        public readonly ?string $lotNumber,
        public readonly ?string $serialNumber,
        public readonly ?string $expiryDate,
        public readonly string  $costingMethod,
        public readonly ?int    $stockLocationId,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            warehouseId:     (int) $data['warehouse_id'],
            productId:       (int) $data['product_id'],
            uomId:           (int) $data['uom_id'],
            quantity:        (string) $data['quantity'],
            costPrice:       (string) $data['cost_price'],
            batchNumber:     $data['batch_number'] ?? null,
            lotNumber:       $data['lot_number'] ?? null,
            serialNumber:    $data['serial_number'] ?? null,
            expiryDate:      $data['expiry_date'] ?? null,
            costingMethod:   $data['costing_method'] ?? 'fifo',
            stockLocationId: isset($data['stock_location_id']) ? (int) $data['stock_location_id'] : null,
        );
    }
}
