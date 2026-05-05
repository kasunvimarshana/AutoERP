<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\Events;

use Modules\Rental\Domain\Entities\Rental;

class RentalStarted
{
    public function __construct(
        public readonly Rental $rental,
    ) {}
}
