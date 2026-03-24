<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class OrganizationUnitCollection extends ResourceCollection
{
    public $collects = OrganizationUnitResource::class;
}
