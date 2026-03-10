<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Infrastructure\Persistence\Models\Category;
use App\Infrastructure\Persistence\Repositories\BaseRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * CategoryController
 *
 * CRUD for product categories.  Uses the dynamic BaseRepository directly
 * since categories have no complex domain logic.
 */
class CategoryController extends Controller
{
    private BaseRepository $repo;

    public function __construct(Category $category)
    {
        // Inline anonymous repository — no dedicated class needed for simple CRUD
        $this->repo = new class($category) extends BaseRepository {};
    }

    // GET /api/categories
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $perPage  = (int) $request->get('per_page', 50);

        $categories = $this->repo->paginate(
            filters:   ['tenant_id' => $tenantId, 'is_active' => true],
            perPage:   $perPage,
            relations: ['children'],
            orderBy:   ['sort_order' => 'asc', 'name' => 'asc']
        );

        return response()->json($categories);
    }

    // GET /api/categories/{id}
    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $category = $this->repo->findBy(['id' => $id, 'tenant_id' => $tenantId], ['*'], ['children', 'products']);

        if ($category === null) {
            return response()->json(['message' => 'Category not found.', 'error' => true], 404);
        }

        return response()->json(['data' => $category]);
    }

    // POST /api/categories
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['required', 'string', 'max:255'],
            'parent_id'   => ['sometimes', 'nullable', 'string', 'uuid'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'sort_order'  => ['sometimes', 'integer'],
            'metadata'    => ['sometimes', 'array'],
        ]);

        $data['tenant_id'] = $request->attributes->get('tenant_id');

        $category = $this->repo->create($data);

        return response()->json(['data' => $category], 201);
    }

    // PUT /api/categories/{id}
    public function update(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:255'],
            'slug'        => ['sometimes', 'string', 'max:255'],
            'parent_id'   => ['sometimes', 'nullable', 'string', 'uuid'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'sort_order'  => ['sometimes', 'integer'],
            'is_active'   => ['sometimes', 'boolean'],
            'metadata'    => ['sometimes', 'array'],
        ]);

        $tenantId = $request->attributes->get('tenant_id');

        // Guard: ensure category belongs to this tenant
        $existing = $this->repo->findBy(['id' => $id, 'tenant_id' => $tenantId]);
        if ($existing === null) {
            return response()->json(['message' => 'Category not found.', 'error' => true], 404);
        }

        $category = $this->repo->update($id, $data);

        return response()->json(['data' => $category]);
    }

    // DELETE /api/categories/{id}
    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');

        $existing = $this->repo->findBy(['id' => $id, 'tenant_id' => $tenantId]);
        if ($existing === null) {
            return response()->json(['message' => 'Category not found.', 'error' => true], 404);
        }

        $this->repo->softDelete($id);

        return response()->json(['message' => 'Category deleted successfully.']);
    }
}
