<?php

declare(strict_types=1);

namespace Modules\Location\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class LocationCollection extends ResourceCollection
{
    public $collects = LocationResource::class;
}
