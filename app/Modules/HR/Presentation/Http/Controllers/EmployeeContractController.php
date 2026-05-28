<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\HR\Application\Contracts\UseCases\EmployeeContracts\CreateEmployeeContractServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeContracts\DeleteEmployeeContractServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeContracts\GetEmployeeContractServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeContracts\ListEmployeeContractsServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeContracts\UpdateEmployeeContractServiceInterface;
use Modules\HR\Presentation\Http\Requests\ListEmployeeContractRequest;
use Modules\HR\Presentation\Http\Requests\UpsertEmployeeContractRequest;
use Modules\HR\Presentation\Http\Resources\EmployeeContractResource;

final class EmployeeContractController extends Controller
{
    public function __construct(
        private readonly ListEmployeeContractsServiceInterface $listService,
        private readonly GetEmployeeContractServiceInterface $getService,
        private readonly CreateEmployeeContractServiceInterface $createService,
        private readonly UpdateEmployeeContractServiceInterface $updateService,
        private readonly DeleteEmployeeContractServiceInterface $deleteService,
    ) {
    }

    public function index(ListEmployeeContractRequest $request): JsonResponse
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
            'data' => EmployeeContractResource::collection($pagedResult->items)->resolve(),
            'meta' => [
                'total' => $pagedResult->total,
                'page' => $pagedResult->page,
                'per_page' => $pagedResult->perPage,
                'page_count' => $pagedResult->pageCount(),
                'has_more' => $pagedResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|EmployeeContractResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new EmployeeContractResource($result->valueOrFail());
    }

    public function store(UpsertEmployeeContractRequest $request): JsonResponse|EmployeeContractResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new EmployeeContractResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertEmployeeContractRequest $request, int|string $id): JsonResponse|EmployeeContractResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new EmployeeContractResource($result->valueOrFail());
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
