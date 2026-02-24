<?php

namespace Modules\Expense\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Expense\Application\UseCases\CreateExpenseCategoryUseCase;
use Modules\Expense\Domain\Contracts\ExpenseCategoryRepositoryInterface;
use Modules\Expense\Presentation\Requests\StoreExpenseCategoryRequest;

class ExpenseCategoryController extends Controller
{
    public function __construct(
        private ExpenseCategoryRepositoryInterface $categoryRepo,
        private CreateExpenseCategoryUseCase       $createUseCase,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->categoryRepo->findByTenant(auth()->user()?->tenant_id));
    }

    public function store(StoreExpenseCategoryRequest $request): JsonResponse
    {
        $category = $this->createUseCase->execute(
            array_merge($request->validated(), ['tenant_id' => auth()->user()?->tenant_id])
        );

        return response()->json($category, 201);
    }

    public function show(string $id): JsonResponse
    {
        $category = $this->categoryRepo->findById($id);

        if (! $category) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($category);
    }

    public function update(StoreExpenseCategoryRequest $request, string $id): JsonResponse
    {
        $category = $this->categoryRepo->update($id, $request->validated());

        return response()->json($category);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->categoryRepo->delete($id);

        return response()->json(null, 204);
    }
}
