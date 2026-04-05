<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Domain\RepositoryInterfaces\ExpenseCategoryRepositoryInterface;

class ExpenseCategoryController extends Controller
{
    public function __construct(private readonly ExpenseCategoryRepositoryInterface $repo) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int)$request->query('tenant_id', 1);
        return response()->json($this->repo->findByTenant($tenantId));
    }

    public function store(Request $request): JsonResponse
    {
        $category = $this->repo->create($request->all());
        return response()->json($category, 201);
    }

    public function show(int $id): JsonResponse
    {
        $category = $this->repo->findById($id);
        if (!$category) return response()->json(['message' => 'Not found'], 404);
        return response()->json($category);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = $this->repo->update($id, $request->all());
        if (!$category) return response()->json(['message' => 'Not found'], 404);
        return response()->json($category);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->repo->delete($id);
        return response()->json(null, 204);
    }
}
