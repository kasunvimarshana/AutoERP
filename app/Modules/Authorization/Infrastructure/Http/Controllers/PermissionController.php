<?php
namespace Modules\Authorization\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Authorization\Application\Contracts\CreatePermissionServiceInterface;
use Modules\Authorization\Application\Contracts\DeletePermissionServiceInterface;
use Modules\Authorization\Application\DTOs\PermissionData;
use Modules\Authorization\Domain\RepositoryInterfaces\PermissionRepositoryInterface;
use Modules\Authorization\Infrastructure\Http\Resources\PermissionResource;

class PermissionController extends Controller
{
    public function __construct(
        private readonly PermissionRepositoryInterface $repository,
        private readonly CreatePermissionServiceInterface $createService,
        private readonly DeletePermissionServiceInterface $deleteService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->repository->findAll());
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:150'],
        ]);
        $permission = $this->createService->execute(new PermissionData(
            name:        $request->string('name')->value(),
            description: $request->input('description'),
        ));
        return response()->json(new PermissionResource($permission), 201);
    }

    public function show(int $id): JsonResponse
    {
        $permission = $this->repository->findById($id);
        if (!$permission) return response()->json(['message' => 'Not found'], 404);
        return response()->json(new PermissionResource($permission));
    }

    public function destroy(int $id): JsonResponse
    {
        $permission = $this->repository->findById($id);
        if (!$permission) return response()->json(['message' => 'Not found'], 404);
        $this->deleteService->execute($permission);
        return response()->json(null, 204);
    }
}
