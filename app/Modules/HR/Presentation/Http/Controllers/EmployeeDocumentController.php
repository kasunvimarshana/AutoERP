<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\HR\Application\Contracts\UseCases\EmployeeDocuments\CreateEmployeeDocumentServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeDocuments\DeleteEmployeeDocumentServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeDocuments\GetEmployeeDocumentServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeDocuments\ListEmployeeDocumentsServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeDocuments\UpdateEmployeeDocumentServiceInterface;
use Modules\HR\Presentation\Http\Requests\ListEmployeeDocumentRequest;
use Modules\HR\Presentation\Http\Requests\UpsertEmployeeDocumentRequest;
use Modules\HR\Presentation\Http\Resources\EmployeeDocumentResource;

final class EmployeeDocumentController extends Controller
{
    public function __construct(
        private readonly ListEmployeeDocumentsServiceInterface $listService,
        private readonly GetEmployeeDocumentServiceInterface $getService,
        private readonly CreateEmployeeDocumentServiceInterface $createService,
        private readonly UpdateEmployeeDocumentServiceInterface $updateService,
        private readonly DeleteEmployeeDocumentServiceInterface $deleteService,
    ) {
    }

    public function index(ListEmployeeDocumentRequest $request): JsonResponse
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
            'data' => EmployeeDocumentResource::collection($pagedResult->items)->resolve(),
            'meta' => [
                'total' => $pagedResult->total,
                'page' => $pagedResult->page,
                'per_page' => $pagedResult->perPage,
                'page_count' => $pagedResult->pageCount(),
                'has_more' => $pagedResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|EmployeeDocumentResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new EmployeeDocumentResource($result->valueOrFail());
    }

    public function store(UpsertEmployeeDocumentRequest $request): JsonResponse|EmployeeDocumentResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new EmployeeDocumentResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertEmployeeDocumentRequest $request, int|string $id): JsonResponse|EmployeeDocumentResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new EmployeeDocumentResource($result->valueOrFail());
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