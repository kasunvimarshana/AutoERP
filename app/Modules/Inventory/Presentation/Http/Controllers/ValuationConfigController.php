<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Inventory\Application\Contracts\UseCases\ValuationConfigs\CreateValuationConfigServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\ValuationConfigs\DeleteValuationConfigServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\ValuationConfigs\GetValuationConfigServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\ValuationConfigs\ListValuationConfigsServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\ValuationConfigs\UpdateValuationConfigServiceInterface;
use Modules\Inventory\Presentation\Http\Requests\ListValuationConfigRequest;
use Modules\Inventory\Presentation\Http\Requests\UpsertValuationConfigRequest;
use Modules\Inventory\Presentation\Http\Resources\ValuationConfigResource;

final class ValuationConfigController extends Controller
{
    public function __construct(
        private readonly ListValuationConfigsServiceInterface $listService,
        private readonly GetValuationConfigServiceInterface $getService,
        private readonly CreateValuationConfigServiceInterface $createService,
        private readonly UpdateValuationConfigServiceInterface $updateService,
        private readonly DeleteValuationConfigServiceInterface $deleteService,
    ) {
    }

    public function index(ListValuationConfigRequest $request): JsonResponse
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
            'data' => ValuationConfigResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|ValuationConfigResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new ValuationConfigResource($result->valueOrFail());
    }

    public function store(UpsertValuationConfigRequest $request): JsonResponse|ValuationConfigResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new ValuationConfigResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertValuationConfigRequest $request, int|string $id): JsonResponse|ValuationConfigResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'INVENTORY_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new ValuationConfigResource($result->valueOrFail());
    }

    public function destroy(int|string $id): JsonResponse
    {
        $result = $this->deleteService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }
}