<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain\Events;

use Modules\Pricing\Domain\Entities\PriceList;

class PriceListCreated
{
    public function __construct(public readonly PriceList $priceList) {}
}
