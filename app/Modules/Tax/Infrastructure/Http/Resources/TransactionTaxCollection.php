<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TransactionTaxCollection extends ResourceCollection
{
    /** @var class-string<\Modules\Tax\Infrastructure\Http\Resources\TransactionTaxResource> */
    public $collects = \Modules\Tax\Infrastructure\Http\Resources\TransactionTaxResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $transactionTax) use ($request): array {
                if ($transactionTax instanceof \Modules\Tax\Infrastructure\Http\Resources\TransactionTaxResource) {
                    return $transactionTax->toArray($request);
                }

                return (new \Modules\Tax\Infrastructure\Http\Resources\TransactionTaxResource($transactionTax))->toArray($request);
            })
            ->all();
    }
}
