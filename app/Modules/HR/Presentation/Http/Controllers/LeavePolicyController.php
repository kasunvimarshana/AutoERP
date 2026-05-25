<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\HR\Application\Contracts\UseCases\LeavePolicies\CreateLeavePolicyServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeavePolicies\DeleteLeavePolicyServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeavePolicies\GetLeavePolicyServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeavePolicies\ListLeavePoliciesServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeavePolicies\UpdateLeavePolicyServiceInterface;
use Modules\HR\Presentation\Http\Requests\ListLeavePolicyRequest;
use Modules\HR\Presentation\Http\Requests\UpsertLeavePolicyRequest;
use Modules\HR\Presentation\Http\Resources\LeavePolicyResource;

final class LeavePolicyController extends Controller
{
    public function __construct(
        private readonly ListLeavePoliciesServiceInterface $listService,
        private readonly GetLeavePolicyServiceInterface $getService,
        private readonly CreateLeavePolicyServiceInterface $createService,
        private readonly UpdateLeavePolicyServiceInterface $updateService,
        private readonly DeleteLeavePolicyServiceInterface $deleteService,
    ) {
    }

    public function index(ListLeavePolicyRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 0);
        $page = (int) ($validated['page'] ?? 0);
        unset($validated['per_page'], $validated['page']);

        $result = $this->listService->execute($validated, $perPage, $page);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $pagedResult = $result->valueOrFail();
        if (! $pagedResult instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected list response.'], 500);
        }

        return response()->json([
            'data' => LeavePolicyResource::collection($pagedResult->items)->resolve(),
            'meta' => [
                'total' => $pagedResult->total,
                'page' => $pagedResult->page,
                'per_page' => $pagedResult->perPage,
                'page_count' => $pagedResult->pageCount(),
                'has_more' => $pagedResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|LeavePolicyResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new LeavePolicyResource($result->valueOrFail());
    }

    public function store(UpsertLeavePolicyRequest $request): JsonResponse|LeavePolicyResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new LeavePolicyResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertLeavePolicyRequest $request, int|string $id): JsonResponse|LeavePolicyResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new LeavePolicyResource($result->valueOrFail());
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