<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CustomerContactCollection extends ResourceCollection
{
    /** @var class-string<\Modules\Customer\Infrastructure\Http\Resources\CustomerContactResource> */
    public $collects = \Modules\Customer\Infrastructure\Http\Resources\CustomerContactResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $contact) use ($request): array {
                if ($contact instanceof \Modules\Customer\Infrastructure\Http\Resources\CustomerContactResource) {
                    return $contact->toArray($request);
                }

                return (new \Modules\Customer\Infrastructure\Http\Resources\CustomerContactResource($contact))->toArray($request);
            })
            ->all();
    }
}
