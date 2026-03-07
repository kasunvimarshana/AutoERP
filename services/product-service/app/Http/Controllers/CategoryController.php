<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends BaseController
{
    // -------------------------------------------------------------------------
    // GET /api/categories
    // -------------------------------------------------------------------------

    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);

        $query = Category::query();

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        // Optionally filter to root categories only (parent_id IS NULL)
        if ($request->boolean('roots_only')) {
            $query->whereNull('parent_id');
        }

        // Optionally filter active only
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        // Search by name or slug
        if ($term = $request->input('search')) {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'LIKE', "%{$term}%")
                  ->orWhere('slug', 'LIKE', "%{$term}%");
            });
        }

        // Eager-load children when requested
        if ($request->boolean('with_children')) {
            $query->with('allChildren');
        }

        // Sort
        $sortColumn    = $request->input('sort_by', 'name');
        $sortDirection = $request->input('sort_dir', 'asc');
        $allowedSorts  = ['name', 'slug', 'created_at', 'updated_at'];

        if (in_array($sortColumn, $allowedSorts, true)) {
            $query->orderBy($sortColumn, $sortDirection === 'desc' ? 'desc' : 'asc');
        }

        // Conditional pagination
        if ($request->has('per_page')) {
            $perPage = max(1, (int) $request->input('per_page', 15));
            $categories = $query->paginate($perPage);

            return $this->paginatedResponse($categories, 'Categories retrieved');
        }

        return $this->successResponse($query->get(), 'Categories retrieved');
    }

    // -------------------------------------------------------------------------
    // POST /api/categories
    // -------------------------------------------------------------------------

    public function store(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id'   => ['nullable', 'integer', 'exists:categories,id'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        // Auto-generate unique slug from name
        $slug = $this->generateSlug($validated['name'], $tenantId);

        try {
            $category = Category::create([
                'tenant_id'   => $tenantId,
                'parent_id'   => $validated['parent_id'] ?? null,
                'name'        => $validated['name'],
                'description' => $validated['description'] ?? null,
                'slug'        => $slug,
                'is_active'   => $validated['is_active'] ?? true,
            ]);

            return $this->createdResponse($category->load('parent'), 'Category created successfully');
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to create category', $e->getMessage(), 500);
        }
    }

    // -------------------------------------------------------------------------
    // GET /api/categories/{id}
    // -------------------------------------------------------------------------

    public function show(Request $request, int|string $id): JsonResponse
    {
        $category = Category::with(['parent', 'children', 'products'])->find($id);

        if (! $category) {
            return $this->notFoundResponse('Category not found');
        }

        if (! $this->tenantMatches($request, $category->tenant_id)) {
            return $this->forbiddenResponse('Access denied for this tenant');
        }

        return $this->successResponse($category, 'Category retrieved');
    }

    // -------------------------------------------------------------------------
    // PUT/PATCH /api/categories/{id}
    // -------------------------------------------------------------------------

    public function update(Request $request, int|string $id): JsonResponse
    {
        $category = Category::find($id);

        if (! $category) {
            return $this->notFoundResponse('Category not found');
        }

        if (! $this->tenantMatches($request, $category->tenant_id)) {
            return $this->forbiddenResponse('Access denied for this tenant');
        }

        $validated = $request->validate([
            'name'        => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id'   => ['nullable', 'integer', 'exists:categories,id'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        // Prevent a category from becoming its own parent
        if (isset($validated['parent_id']) && (string) $validated['parent_id'] === (string) $id) {
            return $this->errorResponse('A category cannot be its own parent', null, 422);
        }

        // Regenerate slug when name changes
        if (isset($validated['name']) && $validated['name'] !== $category->name) {
            $validated['slug'] = $this->generateSlug($validated['name'], $category->tenant_id, $id);
        }

        try {
            $category->fill($validated)->save();

            return $this->successResponse($category->fresh(['parent', 'children']), 'Category updated successfully');
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to update category', $e->getMessage(), 500);
        }
    }

    // -------------------------------------------------------------------------
    // DELETE /api/categories/{id}
    // -------------------------------------------------------------------------

    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $category = Category::find($id);

        if (! $category) {
            return $this->notFoundResponse('Category not found');
        }

        if (! $this->tenantMatches($request, $category->tenant_id)) {
            return $this->forbiddenResponse('Access denied for this tenant');
        }

        // Prevent deletion if child categories or products are attached
        if ($category->children()->exists()) {
            return $this->errorResponse(
                'Cannot delete a category that has child categories. Remove or reassign children first.',
                null,
                422
            );
        }

        if ($category->products()->exists()) {
            return $this->errorResponse(
                'Cannot delete a category that contains products. Remove or reassign products first.',
                null,
                422
            );
        }

        try {
            $category->delete();

            return $this->successResponse(null, 'Category deleted successfully');
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to delete category', $e->getMessage(), 500);
        }
    }

    // -------------------------------------------------------------------------
    // GET /api/categories/{id}/products
    // -------------------------------------------------------------------------

    public function products(Request $request, int|string $id): JsonResponse
    {
        $category = Category::find($id);

        if (! $category) {
            return $this->notFoundResponse('Category not found');
        }

        if (! $this->tenantMatches($request, $category->tenant_id)) {
            return $this->forbiddenResponse('Access denied for this tenant');
        }

        $query = $category->products();

        if ($request->has('per_page')) {
            $perPage = max(1, (int) $request->input('per_page', 15));

            return $this->paginatedResponse($query->paginate($perPage), 'Products retrieved');
        }

        return $this->successResponse($query->get(), 'Products retrieved');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function resolveTenantId(Request $request): ?int
    {
        $tenantId = $request->header('X-Tenant-ID') ?? $request->user()?->tenant_id;

        return $tenantId ? (int) $tenantId : null;
    }

    private function tenantMatches(Request $request, mixed $resourceTenantId): bool
    {
        $tenantId = $request->header('X-Tenant-ID');

        if (! $tenantId) {
            return true;
        }

        return (string) $resourceTenantId === (string) $tenantId;
    }

    /**
     * Generate a unique slug for the given name within the tenant.
     */
    private function generateSlug(string $name, ?int $tenantId, int|string|null $excludeId = null): string
    {
        $base  = Str::slug($name);
        $slug  = $base;
        $count = 1;

        while (true) {
            $query = Category::where('slug', $slug);

            if ($tenantId) {
                $query->where('tenant_id', $tenantId);
            }

            if ($excludeId !== null) {
                $query->where('id', '!=', $excludeId);
            }

            if (! $query->exists()) {
                break;
            }

            $slug = $base . '-' . $count++;
        }

        return $slug;
    }
}
