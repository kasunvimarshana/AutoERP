<?php

declare(strict_types=1);

namespace Modules\Pricing\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Pricing\Models\ProductPrice;

class PriceUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public ProductPrice $price, public array $oldValues) {}
}
