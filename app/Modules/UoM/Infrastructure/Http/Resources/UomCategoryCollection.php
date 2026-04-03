<?php

declare(strict_types=1);

namespace Modules\UoM\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class UomCategoryCollection extends ResourceCollection
{
    public $collects = UomCategoryResource::class;
}
