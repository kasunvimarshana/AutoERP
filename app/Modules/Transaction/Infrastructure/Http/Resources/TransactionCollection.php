<?php

declare(strict_types=1);

namespace Modules\Transaction\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class TransactionCollection extends ResourceCollection
{
    public $collects = TransactionResource::class;
}
