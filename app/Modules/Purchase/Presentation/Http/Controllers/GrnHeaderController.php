<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Purchase\Application\Contracts\UseCases\GrnHeaders\CreateGrnHeaderServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\GrnHeaders\DeleteGrnHeaderServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\GrnHeaders\GetGrnHeaderServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\GrnHeaders\ListGrnHeadersServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\GrnHeaders\UpdateGrnHeaderServiceInterface;
use Modules\Purchase\Application\DTOs\ReceiveGoodsDTO;
use Modules\Purchase\Application\UseCases\ReceiveGoodsAction;
use Modules\Purchase\Presentation\Http\Requests\ListGrnHeaderRequest;
use Modules\Purchase\Presentation\Http\Requests\UpsertGrnHeaderRequest;
use Modules\Purchase\Presentation\Http\Resources\GrnHeaderResource;
use Throwable;

final class GrnHeaderController extends Controller
{
    public function __construct(
        private readonly ListGrnHeadersServiceInterface $listService,
        private readonly GetGrnHeaderServiceInterface $getService,
        private readonly CreateGrnHeaderServiceInterface $createService,
        private readonly UpdateGrnHeaderServiceInterface $updateService,
        private readonly DeleteGrnHeaderServiceInterface $deleteService,
        private readonly ReceiveGoodsAction $receiveGoodsAction,
    ) {
    }

    public function index(ListGrnHeaderRequest $request): JsonResponse
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
            'data' => GrnHeaderResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|GrnHeaderResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new GrnHeaderResource($result->valueOrFail());
    }

    public function store(UpsertGrnHeaderRequest $request): JsonResponse|GrnHeaderResource
    {
        try {
            $record = $this->receiveGoodsAction->execute(new ReceiveGoodsDTO($request->validated()));
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return (new GrnHeaderResource($record))->response()->setStatusCode(201);
    }

    public function update(UpsertGrnHeaderRequest $request, int|string $id): JsonResponse|GrnHeaderResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'PURCHASE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new GrnHeaderResource($result->valueOrFail());
    }

    public function destroy(int|string $id): JsonResponse
    {
        $result = $this->deleteService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }

    public function confirm(int|string $id): JsonResponse|GrnHeaderResource
    {
        try {
            return new GrnHeaderResource($this->receiveGoodsAction->confirm((int) $id));
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }
}
