<?php
namespace Modules\Inventory\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class ReserveStockData extends BaseDTO
{
    public function __construct(
        public readonly int $levelId,
        public readonly float $quantity,
        public readonly ?string $referenceType = null,
        public readonly ?int $referenceId = null,
    ) {}
}
