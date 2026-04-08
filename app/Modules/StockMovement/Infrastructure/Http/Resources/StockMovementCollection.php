<?php

declare(strict_types=1);

namespace Modules\StockMovement\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class StockMovementCollection extends ResourceCollection
{
    public $collects = StockMovementResource::class;
}
