<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\OrganizationUnit\Infrastructure\Http\Resources\OrganizationUnitUserResource;

class OrganizationUnitUserCollection extends ResourceCollection
{
    public string $collects = OrganizationUnitUserResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $organizationUnitUser) use ($request): array {
                if ($organizationUnitUser instanceof OrganizationUnitUserResource) {
                    return $organizationUnitUser->toArray($request);
                }

                return (new OrganizationUnitUserResource($organizationUnitUser))->toArray($request);
            })
            ->all();
    }
}
