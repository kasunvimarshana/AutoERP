<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ReturnAuthorizationCollection extends ResourceCollection
{
    public $collects = ReturnAuthorizationResource::class;
}
