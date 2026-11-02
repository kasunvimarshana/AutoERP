<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Modules\VehicleRental\Enums\RentalExpenseStatus;
use Modules\VehicleRental\Http\Requests\RentalExpenseTransitionRequest;
use Modules\VehicleRental\Http\Requests\StoreRentalExpenseRequest;
use Modules\VehicleRental\Http\Resources\RentalExpenseResource;
use Modules\VehicleRental\Models\RentalExpense;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\RentalExpenseService;

final class RentalExpenseController extends Controller
{
    public function __construct(
        private readonly RentalExpenseService $service,
        private readonly RentalAuthorization $auth,
    ) {}

    public function index(StoreRentalExpenseRequest $request): AnonymousResourceCollection
    {
        $context = $request->context();
        $this->auth->assertExpense($context, write: false);

        $expenses = $this->service->paginate(
            $context->tenantId,
            $context->organizationUnitId,
            $request->query(),
            (int) $request->query('per_page', 15),
        );

        return RentalExpenseResource::collection($expenses);
    }

    public function store(StoreRentalExpenseRequest $request): JsonResponse
    {
        $context = $request->context();
        $this->auth->assertExpense($context, write: true);

        $expense = $this->service->create(
            $request->validated(),
            $context->tenantId,
            $context->organizationUnitId,
            $context->actorId,
        );

        return (new RentalExpenseResource($expense))->response()->setStatusCode(201);
    }

    public function show(StoreRentalExpenseRequest $request, RentalExpense $expense): RentalExpenseResource
    {
        $context = $request->context();
        $this->auth->assertExpense($context, write: false);

        $expense = RentalExpense::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->with($this->service->relations())
            ->findOrFail($expense->getKey());

        return new RentalExpenseResource($expense);
    }

    public function transition(RentalExpenseTransitionRequest $request, RentalExpense $expense): RentalExpenseResource
    {
        $context = $request->context();
        $this->auth->assertExpense($context, write: true);

        $expense = RentalExpense::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->findOrFail($expense->getKey());

        $updated = $this->service->transition(
            $expense,
            RentalExpenseStatus::from((string) $request->validated('status')),
            $request->expectedVersion(),
            $context->actorId,
            (array) $request->validated('allocations', []),
        );

        return new RentalExpenseResource($updated);
    }
}
