<?php

declare(strict_types=1);

namespace Modules\UOM\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\UOM\Application\Contracts\UseCases\UomConversions\CreateUomConversionServiceInterface;
use Modules\UOM\Application\Contracts\UseCases\UomConversions\DeleteUomConversionServiceInterface;
use Modules\UOM\Application\Contracts\UseCases\UomConversions\GetUomConversionServiceInterface;
use Modules\UOM\Application\Contracts\UseCases\UomConversions\ListUomConversionsServiceInterface;
use Modules\UOM\Application\Contracts\UseCases\UomConversions\UpdateUomConversionServiceInterface;
use Modules\UOM\Domain\Constants\UomErrorCode;
use Modules\UOM\Presentation\Http\Requests\ListUomConversionRequest;
use Modules\UOM\Presentation\Http\Requests\UpsertUomConversionRequest;
use Modules\UOM\Presentation\Http\Resources\UomConversionResource;

final class UomConversionController extends Controller
{
    public function __construct(
        private readonly ListUomConversionsServiceInterface $listService,
        private readonly GetUomConversionServiceInterface $getService,
        private readonly CreateUomConversionServiceInterface $createService,
        private readonly UpdateUomConversionServiceInterface $updateService,
        private readonly DeleteUomConversionServiceInterface $deleteService,
    ) {
    }

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
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
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

    public function store(UpsertUomConversionRequest $request): JsonResponse|UomConversionResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new UomConversionResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertUomConversionRequest $request, int|string $id): JsonResponse|UomConversionResource
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
