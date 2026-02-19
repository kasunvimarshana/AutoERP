<?php

declare(strict_types=1);

namespace Modules\Purchase\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Purchase\Models\GoodsReceipt;

class GoodsReceiptPosted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public GoodsReceipt $goodsReceipt
    ) {}
}
