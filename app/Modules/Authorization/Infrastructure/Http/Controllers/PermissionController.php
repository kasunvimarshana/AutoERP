<?php
declare(strict_types=1);
namespace Modules\Authorization\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Authorization\Application\Contracts\PermissionServiceInterface;
use Modules\Authorization\Infrastructure\Http\Resources\PermissionResource;

class PermissionController extends Controller
{
    public function __construct(private readonly PermissionServiceInterface $service) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->service->findAll(
            (int) $request->get('per_page', 15),
            (int) $request->get('page', 1)
        );
        return response()->json($result);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(new PermissionResource($this->service->findById($id)));
    }

    public function store(Request $request): JsonResponse
    {
        $permission = $this->service->create($request->all());
        return response()->json(new PermissionResource($permission), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $permission = $this->service->update($id, $request->all());
        return response()->json(new PermissionResource($permission));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);
        return response()->json(null, 204);
    }
}
