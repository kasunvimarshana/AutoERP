<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TaxRateCollection extends ResourceCollection
{
    /** @var class-string<\Modules\Tax\Infrastructure\Http\Resources\TaxRateResource> */
    public $collects = \Modules\Tax\Infrastructure\Http\Resources\TaxRateResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $taxRate) use ($request): array {
                if ($taxRate instanceof \Modules\Tax\Infrastructure\Http\Resources\TaxRateResource) {
                    return $taxRate->toArray($request);
                }

                return (new \Modules\Tax\Infrastructure\Http\Resources\TaxRateResource($taxRate))->toArray($request);
            })
            ->all();
    }
}
