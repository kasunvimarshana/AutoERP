<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UomConversionCollection extends ResourceCollection
{
    /** @var class-string<UomConversionResource> */
    public $collects = UomConversionResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $uomConversion) use ($request): array {
                if ($uomConversion instanceof UomConversionResource) {
                    return $uomConversion->toArray($request);
                }

                return (new UomConversionResource($uomConversion))->toArray($request);
            })
            ->all();
    }
}
