<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class TenantAttachmentCollection extends ResourceCollection
{
    public string $collects = TenantAttachmentResource::class;
}
