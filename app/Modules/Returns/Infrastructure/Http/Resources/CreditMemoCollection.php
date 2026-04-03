<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CreditMemoCollection extends ResourceCollection
{
    public $collects = CreditMemoResource::class;
}
