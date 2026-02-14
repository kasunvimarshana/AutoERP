<?php

namespace App\Modules\Product\Events;

use App\Modules\Product\Models\ProductVariant;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VariantCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public ProductVariant $variant)
    {
    }
}
