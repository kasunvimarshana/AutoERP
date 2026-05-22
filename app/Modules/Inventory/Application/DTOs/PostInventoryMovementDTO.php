<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

final readonly class PostInventoryMovementDTO
{
    public function __construct(
        public MovementLineDTO $line,
    ) {
    }
}
