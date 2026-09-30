<?php

declare(strict_types=1);

namespace Modules\Expense\Http\Controllers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Expense\Http\Requests\ListExpenseTypeRequest;
use Modules\Expense\Http\Requests\UpsertExpenseTypeRequest;
use Modules\Expense\Http\Resources\ExpenseTypeResource;
use Modules\Expense\Models\ExpenseType;
use Modules\Expense\Services\ExpenseTypeService;

final class ExpenseTypeController
{
    public function __construct(private readonly ExpenseTypeService $types) {}

    public function index(ListExpenseTypeRequest $request): AnonymousResourceCollection
    {
        return ExpenseTypeResource::collection($this->types->paginate(
            $request->tenantId(),
            $request->perPage(),
            $request->filled('search') ? (string) $request->input('search') : null,
            $request->has('is_active') ? $request->boolean('is_active') : null,
        ));
    }

    public function options(ListExpenseTypeRequest $request): AnonymousResourceCollection
    {
        return ExpenseTypeResource::collection($this->types->paginate(
            $request->tenantId(),
            100,
            isActive: true,
        ));
    }

    public function store(UpsertExpenseTypeRequest $request): ExpenseTypeResource
    {
        return new ExpenseTypeResource($this->types->create(
            $request->validated(),
            $request->tenantId(),
            $request->currentUserId(),
        ));
    }

    public function update(UpsertExpenseTypeRequest $request, int $expenseType): ExpenseTypeResource
    {
        $type = ExpenseType::query()
            ->where('tenant_id', $request->tenantId())
            ->findOrFail($expenseType);

        return new ExpenseTypeResource($this->types->update(
            $type,
            $request->validated(),
            (int) $request->input('row_version'),
        ));
    }
}
