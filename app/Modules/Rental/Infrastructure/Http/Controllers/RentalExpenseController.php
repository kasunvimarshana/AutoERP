<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Rental\Application\Contracts\CreateRentalExpenseServiceInterface;
use Modules\Rental\Application\Contracts\UpdateRentalExpenseServiceInterface;
use Modules\Rental\Domain\RepositoryInterfaces\RentalExpenseRepositoryInterface;
use Modules\Rental\Infrastructure\Http\Requests\CreateRentalExpenseRequest;
use Modules\Rental\Infrastructure\Http\Requests\UpdateRentalExpenseRequest;
use Modules\Rental\Infrastructure\Http\Resources\RentalExpenseResource;

class RentalExpenseController extends AuthorizedController
{
    public function __construct(
        private readonly RentalExpenseRepositoryInterface $expenseRepository,
        private readonly CreateRentalExpenseServiceInterface $createService,
        private readonly UpdateRentalExpenseServiceInterface $updateService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $expenses = $this->expenseRepository->findByTenant($tenantId, request()->only(['status', 'expense_type']));

        return RentalExpenseResource::collection($expenses);
    }

    public function show(int $id): RentalExpenseResource
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $expense = $this->expenseRepository->findById($tenantId, $id);

        abort_if($expense === null, 404, 'Rental expense not found.');

        return new RentalExpenseResource($expense);
    }

    public function store(CreateRentalExpenseRequest $request): JsonResponse
    {
        $data = array_merge($request->validated(), [
            'tenant_id' => (int) $request->header('X-Tenant-ID'),
            'changed_by' => $request->user()?->id,
        ]);

        return (new RentalExpenseResource($this->createService->execute($data)))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateRentalExpenseRequest $request, int $id): RentalExpenseResource
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $expense = $this->expenseRepository->findById($tenantId, $id);

        abort_if($expense === null, 404, 'Rental expense not found.');

        $data = array_merge($request->validated(), [
            'id' => $id,
            'tenant_id' => $tenantId,
            'changed_by' => $request->user()?->id,
        ]);

        return new RentalExpenseResource($this->updateService->execute($data));
    }

    public function destroy(int $id): Response
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $expense = $this->expenseRepository->findById($tenantId, $id);

        abort_if($expense === null, 404, 'Rental expense not found.');

        $this->expenseRepository->delete($tenantId, $id);

        return response()->noContent();
    }
}
