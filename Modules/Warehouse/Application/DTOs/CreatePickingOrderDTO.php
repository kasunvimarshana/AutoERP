<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\DTOs;

/**
 * Data Transfer Object for creating a picking order.
 *
 * quantity_requested values in lines are typed as string to enforce BCMath arithmetic.
 */
final class CreatePickingOrderDTO
{
    /**
     * @param array<int, array{product_id: int, quantity_requested: string}> $lines
     */
    public function __construct(
        public readonly int     $warehouseId,
        public readonly string  $pickingType,
        public readonly ?string $referenceType,
        public readonly ?int    $referenceId,
        public readonly array   $lines,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $lines = array_map(
            static fn (array $line): array => [
                'product_id'         => (int) $line['product_id'],
                'quantity_requested' => (string) $line['quantity_requested'],
            ],
            $data['lines']
        );

        return new self(
            warehouseId:   (int) $data['warehouse_id'],
            pickingType:   $data['picking_type'],
            referenceType: $data['reference_type'] ?? null,
            referenceId:   isset($data['reference_id']) ? (int) $data['reference_id'] : null,
            lines:         $lines,
        );
    }
}
