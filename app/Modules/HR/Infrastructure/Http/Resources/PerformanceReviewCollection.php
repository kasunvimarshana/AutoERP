<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PerformanceReviewCollection extends ResourceCollection
{
    public $collects = PerformanceReviewResource::class;
}
