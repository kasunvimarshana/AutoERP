<?php

declare(strict_types=1);

namespace Modules\Procurement\Application\Commands;

final readonly class ReceiveGoodsCommand
{
    /**
     * @param  array<int, array{line_id: int, quantity_received: float|string}>  $receivedLines
     */
    public function __construct(
        public int $id,
        public int $tenantId,
        public int $warehouseId,
        public array $receivedLines,
        public ?string $notes,
    ) {}
}
