<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class AttributeGroupCollection extends ResourceCollection
{
    public $collects = AttributeGroupResource::class;
}
