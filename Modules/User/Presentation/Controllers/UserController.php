<?php
namespace Modules\User\Presentation\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Shared\Application\ResponseFormatter;
use Modules\User\Application\UseCases\CreateUserUseCase;
use Modules\User\Application\UseCases\InviteUserUseCase;
use Modules\User\Infrastructure\Repositories\UserRepository;
class UserController extends Controller
{
    public function __construct(
        private CreateUserUseCase $createUseCase,
        private InviteUserUseCase $inviteUseCase,
        private UserRepository $repo,
    ) {}
    public function index(Request $request): JsonResponse
    {
        $users = $this->repo->paginate($request->only(['status', 'tenant_id']), 15);
        return ResponseFormatter::paginated($users);
    }
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'tenant_id' => ['required', 'string'],
        ]);
        $user = $this->createUseCase->execute($request->validated());
        return ResponseFormatter::success($user, 'User created.', 201);
    }
    public function show(string $id): JsonResponse
    {
        $user = $this->repo->findById($id);
        if (! $user) return ResponseFormatter::error('Not found.', [], 404);
        return ResponseFormatter::success($user);
    }
    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'avatar_path' => ['nullable', 'string'],
        ]);
        $user = $this->repo->update($id, $request->validated());
        return ResponseFormatter::success($user, 'Updated.');
    }
    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);
        return ResponseFormatter::success(null, 'Deleted.');
    }
    public function invite(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'tenant_id' => ['required', 'string'],
        ]);
        $user = $this->inviteUseCase->execute(array_merge($request->validated(), ['invited_by' => auth()->id()]));
        return ResponseFormatter::success($user, 'User invited.', 201);
    }
}
