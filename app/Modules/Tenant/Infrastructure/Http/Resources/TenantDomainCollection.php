<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class TenantDomainCollection extends ResourceCollection
{
    public $collects = TenantDomainResource::class;
}
