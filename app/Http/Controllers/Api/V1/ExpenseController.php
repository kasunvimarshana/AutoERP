<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function __construct(
        private readonly ExpenseService $expenseService
    ) {}

    public function indexCategories(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $filters = $request->only(['is_active']);

        return response()->json($this->expenseService->paginateCategories($tenantId, $filters));
    }

    public function storeCategory(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('expenses.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['sometimes', 'nullable', 'string', 'max:50'],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['tenant_id'] = $request->user()->tenant_id;

        return response()->json($this->expenseService->createCategory($data), 201);
    }

    public function updateCategory(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('expenses.manage'), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'code' => ['sometimes', 'nullable', 'string', 'max:50'],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        return response()->json($this->expenseService->updateCategory($id, $data));
    }

    public function indexExpenses(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('expenses.view'), 403);

        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);
        $filters = $request->only(['expense_category_id', 'business_location_id', 'date_from', 'date_to']);

        return response()->json($this->expenseService->paginateExpenses($tenantId, $filters, $perPage));
    }

    public function storeExpense(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('expenses.create'), 403);

        $data = $request->validate([
            'expense_category_id' => ['required', 'uuid', 'exists:expense_categories,id'],
            'business_location_id' => ['sometimes', 'nullable', 'uuid', 'exists:business_locations,id'],
            'payment_account_id' => ['sometimes', 'nullable', 'uuid', 'exists:payment_accounts,id'],
            'reference_no' => ['sometimes', 'nullable', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'note' => ['sometimes', 'nullable', 'string'],
            'expense_date' => ['required', 'date'],
        ]);

        $data['tenant_id'] = $request->user()->tenant_id;
        $data['created_by'] = $request->user()->id;

        return response()->json($this->expenseService->createExpense($data), 201);
    }

    public function updateExpense(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('expenses.manage'), 403);

        $data = $request->validate([
            'expense_category_id' => ['sometimes', 'uuid', 'exists:expense_categories,id'],
            'business_location_id' => ['sometimes', 'nullable', 'uuid', 'exists:business_locations,id'],
            'payment_account_id' => ['sometimes', 'nullable', 'uuid', 'exists:payment_accounts,id'],
            'reference_no' => ['sometimes', 'nullable', 'string', 'max:100'],
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'note' => ['sometimes', 'nullable', 'string'],
            'expense_date' => ['sometimes', 'date'],
        ]);

        return response()->json($this->expenseService->updateExpense($id, $data));
    }

    public function destroyExpense(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('expenses.manage'), 403);
        $this->expenseService->deleteExpense($id);

        return response()->json(null, 204);
    }
}
