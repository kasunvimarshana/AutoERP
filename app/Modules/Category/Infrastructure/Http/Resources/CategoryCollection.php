<?php

declare(strict_types=1);

namespace Modules\Category\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CategoryCollection extends ResourceCollection
{
    public $collects = CategoryResource::class;
}
