<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TaxGroupCollection extends ResourceCollection
{
    /** @var class-string<TaxGroupResource> */
    public $collects = TaxGroupResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $taxGroup) use ($request): array {
                if ($taxGroup instanceof TaxGroupResource) {
                    return $taxGroup->toArray($request);
                }

                return (new TaxGroupResource($taxGroup))->toArray($request);
            })
            ->all();
    }
}
