<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TaxGroupCollection extends ResourceCollection
{
    /** @var class-string<\Modules\Tax\Infrastructure\Http\Resources\TaxGroupResource> */
    public $collects = \Modules\Tax\Infrastructure\Http\Resources\TaxGroupResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $taxGroup) use ($request): array {
                if ($taxGroup instanceof \Modules\Tax\Infrastructure\Http\Resources\TaxGroupResource) {
                    return $taxGroup->toArray($request);
                }

                return (new \Modules\Tax\Infrastructure\Http\Resources\TaxGroupResource($taxGroup))->toArray($request);
            })
            ->all();
    }
}
