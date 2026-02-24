<?php

namespace Modules\Helpdesk\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Helpdesk\Application\UseCases\CreateTicketCategoryUseCase;
use Modules\Helpdesk\Domain\Contracts\TicketCategoryRepositoryInterface;
use Modules\Helpdesk\Presentation\Requests\StoreTicketCategoryRequest;

class TicketCategoryController extends Controller
{
    public function __construct(
        private TicketCategoryRepositoryInterface $categoryRepo,
        private CreateTicketCategoryUseCase       $createUseCase,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->categoryRepo->findByTenant(auth()->user()?->tenant_id));
    }

    public function store(StoreTicketCategoryRequest $request): JsonResponse
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

    public function update(StoreTicketCategoryRequest $request, string $id): JsonResponse
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
