<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class OrganizationUnitCollection extends ResourceCollection
{
    public string $collects = OrganizationUnitResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $organizationUnit) use ($request): array {
                if ($organizationUnit instanceof OrganizationUnitResource) {
                    return $organizationUnit->toArray($request);
                }

                return (new OrganizationUnitResource($organizationUnit))->toArray($request);
            })
            ->all();
    }
}
