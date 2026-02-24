<?php

namespace Modules\DocumentManagement\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\DocumentManagement\Domain\Contracts\DocumentCategoryRepositoryInterface;
use Modules\DocumentManagement\Presentation\Requests\StoreDocumentCategoryRequest;

class DocumentCategoryController extends Controller
{
    public function __construct(
        private DocumentCategoryRepositoryInterface $categoryRepo,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->categoryRepo->findByTenant(auth()->user()?->tenant_id));
    }

    public function store(StoreDocumentCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryRepo->create(
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

    public function update(StoreDocumentCategoryRequest $request, string $id): JsonResponse
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
