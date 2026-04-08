<?php

declare(strict_types=1);

namespace Modules\Currency\Domain\Events;

use Modules\Currency\Domain\Entities\ExchangeRate;

class ExchangeRateCreated
{
    public function __construct(
        public readonly ExchangeRate $exchangeRate,
    ) {}
}
