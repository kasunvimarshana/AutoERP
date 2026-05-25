<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\HR\Application\Contracts\UseCases\EmployeeContacts\CreateEmployeeContactServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeContacts\DeleteEmployeeContactServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeContacts\GetEmployeeContactServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeContacts\ListEmployeeContactsServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeContacts\UpdateEmployeeContactServiceInterface;
use Modules\HR\Presentation\Http\Requests\ListEmployeeContactRequest;
use Modules\HR\Presentation\Http\Requests\UpsertEmployeeContactRequest;
use Modules\HR\Presentation\Http\Resources\EmployeeContactResource;

final class EmployeeContactController extends Controller
{
    public function __construct(
        private readonly ListEmployeeContactsServiceInterface $listService,
        private readonly GetEmployeeContactServiceInterface $getService,
        private readonly CreateEmployeeContactServiceInterface $createService,
        private readonly UpdateEmployeeContactServiceInterface $updateService,
        private readonly DeleteEmployeeContactServiceInterface $deleteService,
    ) {
    }

    public function index(ListEmployeeContactRequest $request): JsonResponse
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
            'data' => EmployeeContactResource::collection($pagedResult->items)->resolve(),
            'meta' => [
                'total' => $pagedResult->total,
                'page' => $pagedResult->page,
                'per_page' => $pagedResult->perPage,
                'page_count' => $pagedResult->pageCount(),
                'has_more' => $pagedResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|EmployeeContactResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new EmployeeContactResource($result->valueOrFail());
    }

    public function store(UpsertEmployeeContactRequest $request): JsonResponse|EmployeeContactResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new EmployeeContactResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertEmployeeContactRequest $request, int|string $id): JsonResponse|EmployeeContactResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new EmployeeContactResource($result->valueOrFail());
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