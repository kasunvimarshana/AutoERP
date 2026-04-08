<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class SalesOrderCollection extends ResourceCollection
{
    public $collects = SalesOrderResource::class;
}
