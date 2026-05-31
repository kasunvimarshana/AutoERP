<?php

declare(strict_types=1);

namespace Modules\Item\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Item\Application\Contracts\UseCases\ItemTypes\ListItemTypesServiceInterface;
use Modules\Item\Presentation\Http\Requests\ListItemTypeRequest;
use Modules\Item\Presentation\Http\Resources\ItemTypeResource;

final class ItemTypeController extends Controller
{
    public function __construct(private readonly ListItemTypesServiceInterface $listService)
    {
    }

    public function index(ListItemTypeRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 0);
        $page = (int) ($validated['page'] ?? 0);
        unset($validated['per_page'], $validated['page']);

        $result = $this->listService->execute($validated, $perPage, $page);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $pageResult = $result->valueOrFail();
        if (! $pageResult instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected list response.'], 500);
        }

        return response()->json([
            'data' => ItemTypeResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }
}
