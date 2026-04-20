<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SupplierContactCollection extends ResourceCollection
{
    /** @var class-string<\Modules\Supplier\Infrastructure\Http\Resources\SupplierContactResource> */
    public $collects = \Modules\Supplier\Infrastructure\Http\Resources\SupplierContactResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $contact) use ($request): array {
                if ($contact instanceof \Modules\Supplier\Infrastructure\Http\Resources\SupplierContactResource) {
                    return $contact->toArray($request);
                }

                return (new \Modules\Supplier\Infrastructure\Http\Resources\SupplierContactResource($contact))->toArray($request);
            })
            ->all();
    }
}
