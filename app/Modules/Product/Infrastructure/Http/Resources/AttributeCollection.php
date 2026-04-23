<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class AttributeCollection extends ResourceCollection
{
    public $collects = AttributeResource::class;
}
