<?php
namespace Modules\User\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\User\Application\Contracts\CreateUserServiceInterface;
use Modules\User\Application\DTOs\UserData;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Modules\User\Infrastructure\Http\Resources\UserResource;

class UserController extends Controller
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly CreateUserServiceInterface $createService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID', 1);
        return response()->json($this->repository->findAll($tenantId));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email'],
            'password'  => ['required', 'string', 'min:8'],
            'tenant_id' => ['required', 'integer'],
        ]);
        $data = new UserData(
            tenantId: $request->integer('tenant_id'),
            name: $request->string('name')->value(),
            email: $request->string('email')->value(),
            password: $request->string('password')->value(),
        );
        $user = $this->createService->execute($data);
        return response()->json(new UserResource($user), 201);
    }

    public function show(int $id): JsonResponse
    {
        $user = $this->repository->findById($id);
        if (!$user) return response()->json(['message' => 'Not found'], 404);
        return response()->json(new UserResource($user));
    }
}
