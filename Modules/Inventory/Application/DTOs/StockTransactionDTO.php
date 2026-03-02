<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

/**
 * Data Transfer Object for recording a stock transaction.
 *
 * Quantity and cost values are typed as string to enforce BCMath arithmetic.
 */
final class StockTransactionDTO
{
    public function __construct(
        public readonly string  $transactionType,
        public readonly int     $warehouseId,
        public readonly int     $productId,
        public readonly int     $uomId,
        public readonly string  $quantity,
        public readonly string  $unitCost,
        public readonly ?string $batchNumber,
        public readonly ?string $lotNumber,
        public readonly ?string $serialNumber,
        public readonly ?string $expiryDate,
        public readonly ?string $notes,
        public readonly bool    $isPharmaceuticalCompliant,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            transactionType:          $data['transaction_type'],
            warehouseId:              (int) $data['warehouse_id'],
            productId:                (int) $data['product_id'],
            uomId:                    (int) $data['uom_id'],
            quantity:                 (string) $data['quantity'],
            unitCost:                 (string) $data['unit_cost'],
            batchNumber:              $data['batch_number'] ?? null,
            lotNumber:                $data['lot_number'] ?? null,
            serialNumber:             $data['serial_number'] ?? null,
            expiryDate:               $data['expiry_date'] ?? null,
            notes:                    $data['notes'] ?? null,
            isPharmaceuticalCompliant: (bool) ($data['is_pharmaceutical_compliant'] ?? false),
        );
    }
}
