<?php

declare(strict_types=1);

namespace Modules\Product\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Product\Models\Product;

class ProductUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Product $product, public array $oldValues) {}
}
