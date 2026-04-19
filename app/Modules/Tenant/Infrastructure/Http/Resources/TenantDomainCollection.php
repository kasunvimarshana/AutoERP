<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Tenant\Infrastructure\Http\Resources\TenantDomainResource;

class TenantDomainCollection extends ResourceCollection
{
    public string $collects = TenantDomainResource::class;
}
