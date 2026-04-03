<?php

declare(strict_types=1);

namespace Modules\Dispatch\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class DispatchLineCollection extends ResourceCollection
{
    public $collects = DispatchLineResource::class;
}
