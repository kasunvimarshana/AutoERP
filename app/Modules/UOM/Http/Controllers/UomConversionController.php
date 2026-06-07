<?php

declare(strict_types=1);

namespace Modules\UOM\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\DTOs\PagedResult;
use Modules\UOM\Constants\UomErrorCode;
use Modules\UOM\Http\Requests\ListUomConversionRequest;
use Modules\UOM\Http\Requests\StoreUomConversionRequest;
use Modules\UOM\Http\Requests\UpdateUomConversionRequest;
use Modules\UOM\Http\Resources\UomConversionResource;
use Modules\UOM\Services\UomConversions\CreateUomConversionService;
use Modules\UOM\Services\UomConversions\DeleteUomConversionService;
use Modules\UOM\Services\UomConversions\GetUomConversionService;
use Modules\UOM\Services\UomConversions\ListUomConversionsService;
use Modules\UOM\Services\UomConversions\UpdateUomConversionService;

final class UomConversionController extends Controller
{
    public function __construct(
        private readonly ListUomConversionsService $listService,
        private readonly GetUomConversionService $getService,
        private readonly CreateUomConversionService $createService,
        private readonly UpdateUomConversionService $updateService,
        private readonly DeleteUomConversionService $deleteService,
    ) {}

    public function index(ListUomConversionRequest $request): JsonResponse
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
            'data' => UomConversionResource::collection($pageResult->items)->resolve(),
            'meta' => $pageResult->paginationMeta(),
        ]);
    }

    public function show(int|string $id): JsonResponse|UomConversionResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new UomConversionResource($result->valueOrFail());
    }

    public function store(StoreUomConversionRequest $request): JsonResponse|UomConversionResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new UomConversionResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpdateUomConversionRequest $request, int|string $id): JsonResponse|UomConversionResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === UomErrorCode::NOT_FOUND ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new UomConversionResource($result->valueOrFail());
    }

    public function activate(int|string $id): JsonResponse|UomConversionResource
    {
        return $this->changeActiveState($id, true);
    }

    public function deactivate(int|string $id): JsonResponse|UomConversionResource
    {
        return $this->changeActiveState($id, false);
    }

    public function destroy(int|string $id): JsonResponse
    {
        $result = $this->deleteService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }

    private function changeActiveState(int|string $id, bool $isActive): JsonResponse|UomConversionResource
    {
        $result = $this->updateService->execute($id, ['is_active' => $isActive]);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === UomErrorCode::NOT_FOUND ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new UomConversionResource($result->valueOrFail());
    }
}
