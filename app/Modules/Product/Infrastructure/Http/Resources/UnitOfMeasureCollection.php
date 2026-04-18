<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UnitOfMeasureCollection extends ResourceCollection
{
    /** @var class-string<\Modules\Product\Infrastructure\Http\Resources\UnitOfMeasureResource> */
    public $collects = \Modules\Product\Infrastructure\Http\Resources\UnitOfMeasureResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $unitOfMeasure) use ($request): array {
                if ($unitOfMeasure instanceof \Modules\Product\Infrastructure\Http\Resources\UnitOfMeasureResource) {
                    return $unitOfMeasure->toArray($request);
                }

                return (new \Modules\Product\Infrastructure\Http\Resources\UnitOfMeasureResource($unitOfMeasure))->toArray($request);
            })
            ->all();
    }
}
