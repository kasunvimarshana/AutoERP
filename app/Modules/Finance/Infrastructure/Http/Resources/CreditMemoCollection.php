<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CreditMemoCollection extends ResourceCollection
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(fn (mixed $cm) => (new CreditMemoResource($cm))->toArray($request)),
            'meta' => [
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
                'per_page' => $this->perPage(),
                'total' => $this->total(),
            ],
        ];
    }
}
