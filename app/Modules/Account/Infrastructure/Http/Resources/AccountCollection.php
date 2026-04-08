<?php

declare(strict_types=1);

namespace Modules\Account\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class AccountCollection extends ResourceCollection
{
    public $collects = AccountResource::class;
}
