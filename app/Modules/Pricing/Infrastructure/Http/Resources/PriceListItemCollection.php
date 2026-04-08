<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PriceListItemCollection extends ResourceCollection
{
    public $collects = PriceListItemResource::class;
}
