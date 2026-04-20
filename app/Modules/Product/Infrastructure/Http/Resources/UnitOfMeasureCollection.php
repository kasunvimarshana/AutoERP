<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UnitOfMeasureCollection extends ResourceCollection
{
    /** @var class-string<UnitOfMeasureResource> */
    public $collects = UnitOfMeasureResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $unitOfMeasure) use ($request): array {
                if ($unitOfMeasure instanceof UnitOfMeasureResource) {
                    return $unitOfMeasure->toArray($request);
                }

                return (new UnitOfMeasureResource($unitOfMeasure))->toArray($request);
            })
            ->all();
    }
}
