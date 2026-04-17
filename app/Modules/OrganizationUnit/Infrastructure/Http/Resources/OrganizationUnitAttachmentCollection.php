<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class OrganizationUnitAttachmentCollection extends ResourceCollection
{
    public string $collects = OrganizationUnitAttachmentResource::class;
}
