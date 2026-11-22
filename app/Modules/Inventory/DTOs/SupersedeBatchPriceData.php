<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Inventory\DTOs;

final readonly class SupersedeBatchPriceData
{
    public function __construct(
        public BatchPriceData $price,
        public int $expectedVersion,
        public string $correctionReason,
    ) {}
}
