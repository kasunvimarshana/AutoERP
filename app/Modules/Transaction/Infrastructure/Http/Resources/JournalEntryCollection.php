<?php

declare(strict_types=1);

namespace Modules\Transaction\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class JournalEntryCollection extends ResourceCollection
{
    public $collects = JournalEntryResource::class;
}
