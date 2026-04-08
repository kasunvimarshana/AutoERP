<?php

declare(strict_types=1);

namespace Modules\Dispatch\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class DispatchCollection extends ResourceCollection
{
    public $collects = DispatchResource::class;
}
