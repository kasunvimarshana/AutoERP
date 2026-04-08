<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PurchaseOrderLineCollection extends ResourceCollection
{
    public $collects = PurchaseOrderLineResource::class;
}
