<?php

declare(strict_types=1);

namespace Modules\Payment\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Payment\Application\Contracts\UseCases\WriteOffs\CreateWriteOffServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\WriteOffs\DeleteWriteOffServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\WriteOffs\GetWriteOffServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\WriteOffs\ListWriteOffsServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\WriteOffs\UpdateWriteOffServiceInterface;
use Modules\Payment\Presentation\Http\Requests\ListWriteOffRequest;
use Modules\Payment\Presentation\Http\Requests\UpsertWriteOffRequest;
use Modules\Payment\Presentation\Http\Resources\WriteOffResource;

final class WriteOffController extends Controller
{
    public function __construct(
        private readonly ListWriteOffsServiceInterface $listService,
        private readonly GetWriteOffServiceInterface $getService,
        private readonly CreateWriteOffServiceInterface $createService,
        private readonly UpdateWriteOffServiceInterface $updateService,
        private readonly DeleteWriteOffServiceInterface $deleteService,
    ) {
    }

    public function index(ListWriteOffRequest $request): JsonResponse
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
            'data' => WriteOffResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|WriteOffResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new WriteOffResource($result->valueOrFail());
    }

    public function store(UpsertWriteOffRequest $request): JsonResponse|WriteOffResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new WriteOffResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertWriteOffRequest $request, int|string $id): JsonResponse|WriteOffResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'PAYMENT_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new WriteOffResource($result->valueOrFail());
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