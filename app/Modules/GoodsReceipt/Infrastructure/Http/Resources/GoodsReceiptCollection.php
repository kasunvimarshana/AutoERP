<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class GoodsReceiptCollection extends ResourceCollection
{
    public $collects = GoodsReceiptResource::class;
}
