<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TransactionTaxCollection extends ResourceCollection
{
    /** @var class-string<TransactionTaxResource> */
    public $collects = TransactionTaxResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $transactionTax) use ($request): array {
                if ($transactionTax instanceof TransactionTaxResource) {
                    return $transactionTax->toArray($request);
                }

                return (new TransactionTaxResource($transactionTax))->toArray($request);
            })
            ->all();
    }
}
