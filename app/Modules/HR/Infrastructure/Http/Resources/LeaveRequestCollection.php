<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class LeaveRequestCollection extends ResourceCollection
{
    public $collects = LeaveRequestResource::class;
}
