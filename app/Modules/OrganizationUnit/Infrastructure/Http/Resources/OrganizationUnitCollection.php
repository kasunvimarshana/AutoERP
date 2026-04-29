<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;

class OrganizationUnitCollection extends ResourceCollection
{
    public function __construct(
        mixed $resource,
        private readonly FileStorageServiceInterface $storage,
    ) {
        parent::__construct($resource);
    }

    public $collects = OrganizationUnitResource::class;

    public function toArray(Request $request): array
    {
        $storage = $this->storage;

        return $this->collection
            ->map(function (mixed $organizationUnit) use ($request, $storage): array {
                if ($organizationUnit instanceof OrganizationUnitResource) {
                    return $organizationUnit->toArray($request);
                }

                return (new OrganizationUnitResource($organizationUnit, $storage))->toArray($request);
            })
            ->all();
    }
}
