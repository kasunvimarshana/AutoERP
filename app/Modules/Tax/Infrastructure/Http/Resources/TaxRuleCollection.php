<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TaxRuleCollection extends ResourceCollection
{
    /** @var class-string<\Modules\Tax\Infrastructure\Http\Resources\TaxRuleResource> */
    public $collects = \Modules\Tax\Infrastructure\Http\Resources\TaxRuleResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $taxRule) use ($request): array {
                if ($taxRule instanceof \Modules\Tax\Infrastructure\Http\Resources\TaxRuleResource) {
                    return $taxRule->toArray($request);
                }

                return (new \Modules\Tax\Infrastructure\Http\Resources\TaxRuleResource($taxRule))->toArray($request);
            })
            ->all();
    }
}
