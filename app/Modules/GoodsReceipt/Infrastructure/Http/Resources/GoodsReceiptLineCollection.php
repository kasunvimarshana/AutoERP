<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class GoodsReceiptLineCollection extends ResourceCollection
{
    public $collects = GoodsReceiptLineResource::class;
}
