<?php
declare(strict_types=1);
namespace Modules\HR\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HR\Application\Contracts\PositionServiceInterface;
use Modules\HR\Infrastructure\Http\Resources\PositionResource;

class PositionController extends Controller
{
    public function __construct(private readonly PositionServiceInterface $service) {}

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
        return response()->json(new PositionResource($this->service->findById($id)));
    }

    public function store(Request $request): JsonResponse
    {
        $pos = $this->service->create($request->all());
        return response()->json(new PositionResource($pos), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $pos = $this->service->update($id, $request->all());
        return response()->json(new PositionResource($pos));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);
        return response()->json(null, 204);
    }

    public function byDepartment(int $departmentId): JsonResponse
    {
        $positions = $this->service->findByDepartment($departmentId);
        return response()->json(array_map(fn($p) => new PositionResource($p), $positions));
    }
}
