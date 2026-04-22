<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SupplierContactCollection extends ResourceCollection
{
    /** @var class-string<SupplierContactResource> */
    public $collects = SupplierContactResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $contact) use ($request): array {
                if ($contact instanceof SupplierContactResource) {
                    return $contact->toArray($request);
                }

                return (new SupplierContactResource($contact))->toArray($request);
            })
            ->all();
    }
}
