<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\HR\Application\Contracts\UseCases\PayslipLines\CreatePayslipLineServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PayslipLines\DeletePayslipLineServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PayslipLines\GetPayslipLineServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PayslipLines\ListPayslipLinesServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PayslipLines\UpdatePayslipLineServiceInterface;
use Modules\HR\Presentation\Http\Requests\ListPayslipLineRequest;
use Modules\HR\Presentation\Http\Requests\UpsertPayslipLineRequest;
use Modules\HR\Presentation\Http\Resources\PayslipLineResource;

final class PayslipLineController extends Controller
{
    public function __construct(
        private readonly ListPayslipLinesServiceInterface $listService,
        private readonly GetPayslipLineServiceInterface $getService,
        private readonly CreatePayslipLineServiceInterface $createService,
        private readonly UpdatePayslipLineServiceInterface $updateService,
        private readonly DeletePayslipLineServiceInterface $deleteService,
    ) {
    }

    public function index(ListPayslipLineRequest $request): JsonResponse
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
            'data' => PayslipLineResource::collection($pagedResult->items)->resolve(),
            'meta' => [
                'total' => $pagedResult->total,
                'page' => $pagedResult->page,
                'per_page' => $pagedResult->perPage,
                'page_count' => $pagedResult->pageCount(),
                'has_more' => $pagedResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|PayslipLineResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new PayslipLineResource($result->valueOrFail());
    }

    public function store(UpsertPayslipLineRequest $request): JsonResponse|PayslipLineResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new PayslipLineResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertPayslipLineRequest $request, int|string $id): JsonResponse|PayslipLineResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new PayslipLineResource($result->valueOrFail());
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