<?php

declare(strict_types=1);

namespace Modules\Payment\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Payment\Application\Contracts\UseCases\Checks\CreateCheckServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\Checks\DeleteCheckServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\Checks\GetCheckServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\Checks\ListChecksServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\Checks\UpdateCheckServiceInterface;
use Modules\Payment\Presentation\Http\Requests\ListCheckRequest;
use Modules\Payment\Presentation\Http\Requests\UpsertCheckRequest;
use Modules\Payment\Presentation\Http\Resources\CheckResource;

final class CheckController extends Controller
{
    public function __construct(
        private readonly ListChecksServiceInterface $listService,
        private readonly GetCheckServiceInterface $getService,
        private readonly CreateCheckServiceInterface $createService,
        private readonly UpdateCheckServiceInterface $updateService,
        private readonly DeleteCheckServiceInterface $deleteService,
    ) {
    }

    public function index(ListCheckRequest $request): JsonResponse
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
            'data' => CheckResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|CheckResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new CheckResource($result->valueOrFail());
    }

    public function store(UpsertCheckRequest $request): JsonResponse|CheckResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new CheckResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertCheckRequest $request, int|string $id): JsonResponse|CheckResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'PAYMENT_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new CheckResource($result->valueOrFail());
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