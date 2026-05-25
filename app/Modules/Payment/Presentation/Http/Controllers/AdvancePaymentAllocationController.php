<?php

declare(strict_types=1);

namespace Modules\Payment\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Payment\Application\Contracts\UseCases\AdvancePaymentAllocations\CreateAdvancePaymentAllocationServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\AdvancePaymentAllocations\DeleteAdvancePaymentAllocationServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\AdvancePaymentAllocations\GetAdvancePaymentAllocationServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\AdvancePaymentAllocations\ListAdvancePaymentAllocationsServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\AdvancePaymentAllocations\UpdateAdvancePaymentAllocationServiceInterface;
use Modules\Payment\Presentation\Http\Requests\ListAdvancePaymentAllocationRequest;
use Modules\Payment\Presentation\Http\Requests\UpsertAdvancePaymentAllocationRequest;
use Modules\Payment\Presentation\Http\Resources\AdvancePaymentAllocationResource;

final class AdvancePaymentAllocationController extends Controller
{
    public function __construct(
        private readonly ListAdvancePaymentAllocationsServiceInterface $listService,
        private readonly GetAdvancePaymentAllocationServiceInterface $getService,
        private readonly CreateAdvancePaymentAllocationServiceInterface $createService,
        private readonly UpdateAdvancePaymentAllocationServiceInterface $updateService,
        private readonly DeleteAdvancePaymentAllocationServiceInterface $deleteService,
    ) {
    }

    public function index(ListAdvancePaymentAllocationRequest $request): JsonResponse
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
            'data' => AdvancePaymentAllocationResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|AdvancePaymentAllocationResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new AdvancePaymentAllocationResource($result->valueOrFail());
    }

    public function store(UpsertAdvancePaymentAllocationRequest $request): JsonResponse|AdvancePaymentAllocationResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new AdvancePaymentAllocationResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertAdvancePaymentAllocationRequest $request, int|string $id): JsonResponse|AdvancePaymentAllocationResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'PAYMENT_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new AdvancePaymentAllocationResource($result->valueOrFail());
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