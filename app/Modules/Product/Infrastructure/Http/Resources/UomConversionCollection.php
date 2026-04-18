<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UomConversionCollection extends ResourceCollection
{
    /** @var class-string<\Modules\Product\Infrastructure\Http\Resources\UomConversionResource> */
    public $collects = \Modules\Product\Infrastructure\Http\Resources\UomConversionResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $uomConversion) use ($request): array {
                if ($uomConversion instanceof \Modules\Product\Infrastructure\Http\Resources\UomConversionResource) {
                    return $uomConversion->toArray($request);
                }

                return (new \Modules\Product\Infrastructure\Http\Resources\UomConversionResource($uomConversion))->toArray($request);
            })
            ->all();
    }
}
