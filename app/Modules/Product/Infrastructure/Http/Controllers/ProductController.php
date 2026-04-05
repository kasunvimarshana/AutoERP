<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Product\Application\Contracts\ProductServiceInterface;

class ProductController extends Controller
{
    public function __construct(private readonly ProductServiceInterface $service) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['category_id','type','status']);
        return response()->json($this->service->findByTenant(
            (int)$request->input('tenant_id'), $filters,
            (int)$request->input('per_page', 15), (int)$request->input('page', 1)
        ));
    }
    public function show(int $id): JsonResponse { return response()->json($this->service->findById($id)); }
    public function store(Request $request): JsonResponse { return response()->json($this->service->create($request->all()), 201); }
    public function update(Request $request, int $id): JsonResponse { return response()->json($this->service->update($id, $request->all())); }
    public function destroy(int $id): JsonResponse { $this->service->delete($id); return response()->json(null, 204); }
}
