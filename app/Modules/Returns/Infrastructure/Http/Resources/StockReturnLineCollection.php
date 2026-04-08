<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class StockReturnLineCollection extends ResourceCollection
{
    public $collects = StockReturnLineResource::class;
}
