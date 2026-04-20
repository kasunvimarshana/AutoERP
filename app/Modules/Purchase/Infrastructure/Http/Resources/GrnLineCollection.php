<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class GrnLineCollection extends ResourceCollection
{
    public $collects = GrnLineResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $item) use ($request): array {
                if ($item instanceof GrnLineResource) {
                    return $item->toArray($request);
                }

                return (new GrnLineResource($item))->toArray($request);
            })
            ->all();
    }
}
