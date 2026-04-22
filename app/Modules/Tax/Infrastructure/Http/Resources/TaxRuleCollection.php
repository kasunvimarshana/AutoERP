<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TaxRuleCollection extends ResourceCollection
{
    /** @var class-string<TaxRuleResource> */
    public $collects = TaxRuleResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $taxRule) use ($request): array {
                if ($taxRule instanceof TaxRuleResource) {
                    return $taxRule->toArray($request);
                }

                return (new TaxRuleResource($taxRule))->toArray($request);
            })
            ->all();
    }
}
