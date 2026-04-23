<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\HR\Application\Contracts\DeleteEmployeeDocumentServiceInterface;
use Modules\HR\Application\Contracts\FindEmployeeDocumentServiceInterface;
use Modules\HR\Application\Contracts\StoreEmployeeDocumentServiceInterface;
use Modules\HR\Domain\Entities\EmployeeDocument;
use Modules\HR\Infrastructure\Http\Requests\StoreEmployeeDocumentRequest;
use Modules\HR\Infrastructure\Http\Resources\EmployeeDocumentResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EmployeeDocumentController extends AuthorizedController
{
    public function __construct(
        protected StoreEmployeeDocumentServiceInterface $storeService,
        protected FindEmployeeDocumentServiceInterface $findService,
        protected DeleteEmployeeDocumentServiceInterface $deleteService,
    ) {}

    public function index(): JsonResponse
    {
        $result = $this->findService->list();

        return Response::json(['data' => EmployeeDocumentResource::collection($result)]);
    }

    public function store(StoreEmployeeDocumentRequest $request): JsonResponse
    {
        $entity = $this->storeService->execute($request->validated());

        return (new EmployeeDocumentResource($entity))->response()->setStatusCode(201);
    }

    public function show(int $employeeDocument): EmployeeDocumentResource
    {
        return new EmployeeDocumentResource($this->findOrFail($employeeDocument));
    }

    public function update(StoreEmployeeDocumentRequest $request, int $employeeDocument): EmployeeDocumentResource
    {
        $this->findOrFail($employeeDocument);
        $payload = $request->validated();
        $payload['id'] = $employeeDocument;
        $updated = $this->storeService->execute($payload);

        return new EmployeeDocumentResource($updated);
    }

    public function destroy(int $employeeDocument): JsonResponse
    {
        $this->findOrFail($employeeDocument);
        $this->deleteService->execute(['id' => $employeeDocument]);

        return Response::json(null, 204);
    }

    private function findOrFail(int $id): EmployeeDocument
    {
        $entity = $this->findService->find($id);
        if (! $entity) {
            throw new NotFoundHttpException('Employee document not found.');
        }

        return $entity;
    }
}
