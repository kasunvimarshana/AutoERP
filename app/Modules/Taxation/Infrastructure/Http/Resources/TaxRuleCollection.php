<?php

declare(strict_types=1);

namespace Modules\Taxation\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class TaxRuleCollection extends ResourceCollection
{
    public $collects = TaxRuleResource::class;
}
