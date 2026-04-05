<?php
declare(strict_types=1);
namespace Modules\HR\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HR\Application\Contracts\DepartmentServiceInterface;
use Modules\HR\Infrastructure\Http\Resources\DepartmentResource;

class DepartmentController extends Controller
{
    public function __construct(private readonly DepartmentServiceInterface $service) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->get('tenant_id', 0);
        $result = $this->service->findByTenant(
            $tenantId,
            (int) $request->get('per_page', 15),
            (int) $request->get('page', 1)
        );
        return response()->json($result);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(new DepartmentResource($this->service->findById($id)));
    }

    public function store(Request $request): JsonResponse
    {
        $dept = $this->service->create($request->all());
        return response()->json(new DepartmentResource($dept), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $dept = $this->service->update($id, $request->all());
        return response()->json(new DepartmentResource($dept));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);
        return response()->json(null, 204);
    }
}
