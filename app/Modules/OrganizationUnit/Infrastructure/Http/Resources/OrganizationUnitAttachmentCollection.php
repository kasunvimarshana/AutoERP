<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;

class OrganizationUnitAttachmentCollection extends ResourceCollection
{
    public function __construct(
        mixed $resource,
        private readonly FileStorageServiceInterface $storage,
    ) {
        parent::__construct($resource);
    }

    public string $collects = OrganizationUnitAttachmentResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(fn (mixed $attachment): array => (new OrganizationUnitAttachmentResource($attachment, $this->storage))->toArray($request))
            ->all();
    }
}
