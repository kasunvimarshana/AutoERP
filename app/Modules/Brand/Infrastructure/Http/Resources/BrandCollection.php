<?php

declare(strict_types=1);

namespace Modules\Brand\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class BrandCollection extends ResourceCollection
{
    public $collects = BrandResource::class;
}
