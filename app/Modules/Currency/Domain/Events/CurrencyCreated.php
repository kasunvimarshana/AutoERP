<?php

declare(strict_types=1);

namespace Modules\Currency\Domain\Events;

use Modules\Currency\Domain\Entities\Currency;

class CurrencyCreated
{
    public function __construct(
        public readonly Currency $currency,
    ) {}
}
