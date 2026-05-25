<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\HR\Application\Contracts\UseCases\PayrollRuns\CreatePayrollRunServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PayrollRuns\DeletePayrollRunServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PayrollRuns\GetPayrollRunServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PayrollRuns\ListPayrollRunsServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PayrollRuns\UpdatePayrollRunServiceInterface;
use Modules\HR\Presentation\Http\Requests\ListPayrollRunRequest;
use Modules\HR\Presentation\Http\Requests\UpsertPayrollRunRequest;
use Modules\HR\Presentation\Http\Resources\PayrollRunResource;

final class PayrollRunController extends Controller
{
    public function __construct(
        private readonly ListPayrollRunsServiceInterface $listService,
        private readonly GetPayrollRunServiceInterface $getService,
        private readonly CreatePayrollRunServiceInterface $createService,
        private readonly UpdatePayrollRunServiceInterface $updateService,
        private readonly DeletePayrollRunServiceInterface $deleteService,
    ) {
    }

    public function index(ListPayrollRunRequest $request): JsonResponse
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
            'data' => PayrollRunResource::collection($pagedResult->items)->resolve(),
            'meta' => [
                'total' => $pagedResult->total,
                'page' => $pagedResult->page,
                'per_page' => $pagedResult->perPage,
                'page_count' => $pagedResult->pageCount(),
                'has_more' => $pagedResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|PayrollRunResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new PayrollRunResource($result->valueOrFail());
    }

    public function store(UpsertPayrollRunRequest $request): JsonResponse|PayrollRunResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new PayrollRunResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertPayrollRunRequest $request, int|string $id): JsonResponse|PayrollRunResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new PayrollRunResource($result->valueOrFail());
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