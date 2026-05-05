<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\HR\Application\Contracts\CreatePayrollItemServiceInterface;
use Modules\HR\Application\Contracts\FindPayrollItemServiceInterface;
use Modules\HR\Application\Contracts\UpdatePayrollItemServiceInterface;
use Modules\HR\Domain\Entities\PayrollItem;
use Modules\HR\Infrastructure\Http\Requests\StorePayrollItemRequest;
use Modules\HR\Infrastructure\Http\Requests\UpdatePayrollItemRequest;
use Modules\HR\Infrastructure\Http\Resources\PayrollItemResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PayrollItemController extends AuthorizedController
{
    public function __construct(
        protected CreatePayrollItemServiceInterface $createService,
        protected UpdatePayrollItemServiceInterface $updateService,
        protected FindPayrollItemServiceInterface $findService,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', PayrollItem::class);
        $result = $this->findService->list();

        return Response::json(['data' => PayrollItemResource::collection($result)]);
    }

    public function store(StorePayrollItemRequest $request): JsonResponse
    {
        $this->authorize('create', PayrollItem::class);
        $entity = $this->createService->execute($request->validated());

        return (new PayrollItemResource($entity))->response()->setStatusCode(201);
    }

    public function show(int $payrollItem): PayrollItemResource
    {
        $entity = $this->findOrFail($payrollItem);
        $this->authorize('view', $entity);

        return new PayrollItemResource($entity);
    }

    public function update(UpdatePayrollItemRequest $request, int $payrollItem): PayrollItemResource
    {
        $entity = $this->findOrFail($payrollItem);
        $this->authorize('update', $entity);
        $payload = $request->validated();
        $payload['id'] = $payrollItem;
        $updated = $this->updateService->execute($payload);

        return new PayrollItemResource($updated);
    }

    public function destroy(int $payrollItem): JsonResponse
    {
        $entity = $this->findOrFail($payrollItem);
        $this->authorize('delete', $entity);

        return Response::json(null, 204);
    }

    private function findOrFail(int $id): PayrollItem
    {
        $entity = $this->findService->find($id);
        if (! $entity) {
            throw new NotFoundHttpException('Payroll item not found.');
        }

        return $entity;
    }
}
