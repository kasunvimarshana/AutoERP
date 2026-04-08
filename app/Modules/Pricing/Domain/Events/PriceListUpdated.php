<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain\Events;

use Modules\Pricing\Domain\Entities\PriceList;

class PriceListUpdated
{
    public function __construct(public readonly PriceList $priceList) {}
}
