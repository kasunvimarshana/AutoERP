<?php
namespace Modules\User\Presentation\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Shared\Application\ResponseFormatter;
use Modules\User\Application\UseCases\AssignRoleUseCase;
use Modules\User\Infrastructure\Repositories\RoleRepository;
class RoleController extends Controller
{
    public function __construct(
        private RoleRepository $repo,
        private AssignRoleUseCase $assignRoleUseCase,
    ) {}
    public function index(Request $request): JsonResponse
    {
        $roles = $this->repo->paginate($request->only(['tenant_id']), 15);
        return ResponseFormatter::paginated($roles);
    }
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'tenant_id' => ['required', 'string'],
            'guard_name' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ]);
        $role = $this->repo->create(array_merge(
            $request->validated(),
            ['id' => (string) \Illuminate\Support\Str::uuid()]
        ));
        return ResponseFormatter::success($role, 'Role created.', 201);
    }
    public function show(string $id): JsonResponse
    {
        $role = $this->repo->findById($id);
        if (! $role) return ResponseFormatter::error('Not found.', [], 404);
        return ResponseFormatter::success($role);
    }
    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);
        $role = $this->repo->update($id, $request->validated());
        return ResponseFormatter::success($role, 'Updated.');
    }
    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);
        return ResponseFormatter::success(null, 'Deleted.');
    }
    public function assign(string $id, string $userId): JsonResponse
    {
        $this->assignRoleUseCase->execute(['user_id' => $userId, 'role_id' => $id]);
        return ResponseFormatter::success(null, 'Role assigned.');
    }
}
