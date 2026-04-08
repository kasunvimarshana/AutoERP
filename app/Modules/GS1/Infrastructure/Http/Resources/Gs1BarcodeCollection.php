<?php

declare(strict_types=1);

namespace Modules\GS1\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class Gs1BarcodeCollection extends ResourceCollection
{
    public $collects = Gs1BarcodeResource::class;
}
