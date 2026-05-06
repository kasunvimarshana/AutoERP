<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Http\Resources;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;

class OrganizationUnitCollection extends ResourceCollection
{
    public function __construct(
        mixed $resource,
        private readonly FileStorageServiceInterface $storage,
    ) {
        parent::__construct($resource);
    }

    /** @var class-string|null Prevent mapInto from instantiating OrganizationUnitResource with wrong constructor args */
    public $collects = null;

    /**
     * Override collectResource to preserve raw entities without calling mapInto.
     * OrganizationUnitResource requires FileStorageServiceInterface as 2nd arg,
     * making it incompatible with mapInto(class-string) instantiation.
     *
     * @param  mixed  $resource
     * @return mixed
     */
    protected function collectResource(mixed $resource): mixed
    {
        if ($resource instanceof Paginator) {
            $this->collection = collect($resource->getCollection());
            $this->resource = $resource->setCollection($this->collection);

            return $this->resource;
        }

        if ($resource instanceof Collection) {
            $this->collection = $resource;

            return $this->collection;
        }

        $this->collection = collect((array) $resource);

        return $this->collection;
    }

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
