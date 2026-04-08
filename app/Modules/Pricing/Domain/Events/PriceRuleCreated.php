<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain\Events;

use Modules\Pricing\Domain\Entities\PriceRule;

class PriceRuleCreated
{
    public function __construct(public readonly PriceRule $priceRule) {}
}
