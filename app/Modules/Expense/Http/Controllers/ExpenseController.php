<?php

declare(strict_types=1);

namespace Modules\Expense\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Expense\Http\Requests\ListExpenseRequest;
use Modules\Expense\Http\Requests\ReverseExpenseRequest;
use Modules\Expense\Http\Requests\StoreExpenseRequest;
use Modules\Expense\Http\Resources\ExpenseResource;
use Modules\Expense\Models\Expense;
use Modules\Expense\Services\ExpensePostingService;
use Modules\Expense\Services\ExpenseReversalService;
use Modules\Payment\Contracts\PaymentMethodProviderInterface;

final class ExpenseController
{
    public function index(ListExpenseRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(Expense::query(), $request->tenantId(), $request->organizationUnitId())
            ->with(['expenseType', 'organizationUnit', 'currency']);
        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where(fn (Builder $scope): Builder => $scope
                ->where('expense_number', 'like', "%{$term}%")
                ->orWhere('reference_number', 'like', "%{$term}%")
                ->orWhere('expense_type_name_snapshot', 'like', "%{$term}%"));
        }
        if ($request->filled('expense_type_id')) {
            $query->where('expense_type_id', $request->integer('expense_type_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('expense_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('expense_date', '<=', $request->input('date_to'));
        }

        return ExpenseResource::collection(
            $query->latest('expense_date')->latest('id')->paginate($request->perPage()),
        );
    }

    public function store(StoreExpenseRequest $request, ExpensePostingService $service): ExpenseResource
    {
        return new ExpenseResource($service->createAndPost($request->toData()));
    }

    public function show(ListExpenseRequest $request, int $expense): ExpenseResource
    {
        return new ExpenseResource($this->scope(
            Expense::query(),
            $request->tenantId(),
            $request->organizationUnitId(),
        )->with(['expenseType', 'organizationUnit', 'currency'])->findOrFail($expense));
    }

    public function reverse(
        ReverseExpenseRequest $request,
        int $expense,
        ExpenseReversalService $service,
    ): ExpenseResource {
        $row = $this->scope(
            Expense::query(),
            $request->tenantId(),
            $request->organizationUnitId(),
        )->findOrFail($expense);

        return new ExpenseResource($service->reverse(
            $row,
            (int) $request->input('expected_version'),
            (string) $request->input('reversal_date'),
            (string) $request->input('reason'),
            $request->currentUserId(),
        ));
    }

    public function paymentMethods(
        ListExpenseRequest $request,
        PaymentMethodProviderInterface $methods,
    ): JsonResponse {
        $organizationUnitId = $request->organizationUnitId();
        if ($organizationUnitId === null) {
            throw new \LogicException('Expense payment methods require an active organization unit.');
        }

        return response()->json(['data' => array_map(
            static fn ($method): array => [
                'id' => $method->id,
                'code' => $method->code,
                'name' => $method->name,
                'type' => $method->type,
                'requires_reference' => $method->requiresReference,
                'requires_instrument_details' => $method->requiresInstrumentDetails,
            ],
            $methods->outboundMethods($request->tenantId(), $organizationUnitId),
        )]);
    }

    private function scope(Builder $query, int $tenantId, ?int $organizationUnitId): Builder
    {
        return $query
            ->where('tenant_id', $tenantId)
            ->where('organization_unit_id', $organizationUnitId);
    }
}
