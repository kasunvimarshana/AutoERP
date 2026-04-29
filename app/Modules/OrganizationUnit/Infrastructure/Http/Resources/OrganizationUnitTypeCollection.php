<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class OrganizationUnitTypeCollection extends ResourceCollection
{
    public string $collects = OrganizationUnitTypeResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $organizationUnitType) use ($request): array {
                if ($organizationUnitType instanceof OrganizationUnitTypeResource) {
                    return $organizationUnitType->toArray($request);
                }

                return (new OrganizationUnitTypeResource($organizationUnitType))->toArray($request);
            })
            ->all();
    }
}
