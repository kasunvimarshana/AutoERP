<?php

declare(strict_types=1);

namespace Modules\ReferenceData\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

trait BuildsReferenceResponses
{
    /** @param class-string<JsonResource> $resourceClass */
    private function pageResponse(
        LengthAwarePaginator $page,
        string $resourceClass,
    ): JsonResponse {
        return response()->json([
            'data' => $resourceClass::collection($page->items())->resolve(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'from' => $page->firstItem(),
                'last_page' => max(1, $page->lastPage()),
                'per_page' => $page->perPage(),
                'to' => $page->lastItem(),
                'total' => $page->total(),
            ],
        ]);
    }
}
