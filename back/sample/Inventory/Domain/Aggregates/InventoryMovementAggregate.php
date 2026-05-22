<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Aggregates;

use Modules\Inventory\Application\DTOs\MovementLineDTO;
use Modules\Inventory\Application\DTOs\PostInventoryMovementDTO;
use Modules\Inventory\Domain\Exceptions\InventoryConfigurationException;

final readonly class InventoryMovementAggregate
{
    public function __construct(
        private MovementLineDTO $line,
    ) {
    }

    public static function fromPostDTO(PostInventoryMovementDTO $dto): self
    {
        $line = $dto->line;

        if ($line->quantity <= 0) {
            throw new InventoryConfigurationException('Movement quantity must be greater than zero.');
        }

        if ($line->warehouseId === null && $line->locationId !== null) {
            throw new InventoryConfigurationException('Warehouse is required when location is provided.');
        }

        if ($line->serialId !== null && $line->quantity !== 1.0) {
            throw new InventoryConfigurationException('Serial tracked movement must have quantity equal to 1.');
        }

        return new self($line);
    }

    public function line(): MovementLineDTO
    {
        return $this->line;
    }
}
