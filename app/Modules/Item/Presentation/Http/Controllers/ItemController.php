<?php

declare(strict_types=1);

namespace Modules\Item\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Core\Application\Results\Result;
use Modules\Item\Application\Contracts\UseCases\Items\CreateItemServiceInterface;
use Modules\Item\Application\Contracts\UseCases\Items\DeleteItemServiceInterface;
use Modules\Item\Application\Contracts\UseCases\Items\GetItemServiceInterface;
use Modules\Item\Application\Contracts\UseCases\Items\GetItemSetupSummaryServiceInterface;
use Modules\Item\Application\Contracts\UseCases\Items\ListItemsServiceInterface;
use Modules\Item\Application\Contracts\UseCases\Items\UpdateItemServiceInterface;
use Modules\Item\Presentation\Http\Requests\ListItemRequest;
use Modules\Item\Presentation\Http\Requests\UpsertItemRequest;
use Modules\Item\Presentation\Http\Resources\ItemResource;

final class ItemController extends Controller
{
    public function __construct(
        private readonly ListItemsServiceInterface $listService,
        private readonly GetItemServiceInterface $getService,
        private readonly CreateItemServiceInterface $createService,
        private readonly UpdateItemServiceInterface $updateService,
        private readonly DeleteItemServiceInterface $deleteService,
        private readonly GetItemSetupSummaryServiceInterface $setupSummaryService,
    ) {
    }

    public function index(ListItemRequest $request): JsonResponse
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
            'data' => ItemResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|ItemResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new ItemResource($result->valueOrFail());
    }

    public function store(UpsertItemRequest $request): JsonResponse|ItemResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new ItemResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertItemRequest $request, int|string $id): JsonResponse|ItemResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'ITEM_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new ItemResource($result->valueOrFail());
    }

    public function destroy(int|string $id): JsonResponse
    {
        $result = $this->deleteService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }

    public function activate(int|string $id): JsonResponse|ItemResource
    {
        return $this->setActiveState($id, true);
    }

    public function deactivate(int|string $id): JsonResponse|ItemResource
    {
        return $this->setActiveState($id, false);
    }

    public function capabilities(int|string $id): JsonResponse
    {
        return $this->summaryResponse($this->setupSummaryService->capabilities($id));
    }

    public function inventorySummary(int|string $id): JsonResponse
    {
        return $this->summaryResponse($this->setupSummaryService->inventorySummary($id));
    }

    public function pricingReferences(int|string $id): JsonResponse
    {
        return $this->summaryResponse($this->setupSummaryService->pricingReferences($id));
    }

    public function usageSummary(int|string $id): JsonResponse
    {
        return $this->summaryResponse($this->setupSummaryService->capabilities($id));
    }

    public function uomSetup(Request $request, int|string $id): JsonResponse
    {
        $context = $request->query('context');

        return $this->summaryResponse($this->setupSummaryService->uomSetup($id, is_string($context) ? $context : null));
    }

    public function previewTypeSetup(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'type' => ['nullable', 'string', 'max:255'],
            'base_uom_id' => ['nullable', 'integer', 'min:1', 'exists:unit_of_measures,id'],
            'is_stockable' => ['nullable', 'boolean'],
            'is_purchasable' => ['nullable', 'boolean'],
            'is_sellable' => ['nullable', 'boolean'],
            'is_service' => ['nullable', 'boolean'],
            'is_rentable' => ['nullable', 'boolean'],
            'is_chargeable' => ['nullable', 'boolean'],
            'is_batch_tracked' => ['nullable', 'boolean'],
            'is_serial_tracked' => ['nullable', 'boolean'],
            'combo_items' => ['nullable', 'array'],
        ]);

        return $this->summaryResponse($this->setupSummaryService->previewTypeSetup($payload));
    }

    private function setActiveState(int|string $id, bool $active): JsonResponse|ItemResource
    {
        $result = $this->updateService->execute($id, ['is_active' => $active]);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'ITEM_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new ItemResource($result->valueOrFail());
    }

    private function summaryResponse(Result $result): JsonResponse
    {
        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'ITEM_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }
}
