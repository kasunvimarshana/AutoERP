<?php
declare(strict_types=1);
namespace Modules\User\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\User\Application\Contracts\CreateUserServiceInterface;
use Modules\User\Application\Contracts\DeleteUserServiceInterface;
use Modules\User\Application\Contracts\GetUserServiceInterface;
use Modules\User\Application\Contracts\UpdateUserServiceInterface;
use Modules\User\Application\DTOs\CreateUserData;
use Modules\User\Application\DTOs\UpdateUserData;
use Modules\User\Infrastructure\Http\Resources\UserResource;

class UserController extends Controller
{
    public function __construct(
        private readonly GetUserServiceInterface $getService,
        private readonly CreateUserServiceInterface $createService,
        private readonly UpdateUserServiceInterface $updateService,
        private readonly DeleteUserServiceInterface $deleteService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->get('tenant_id', 0);
        $result = $this->getService->findByTenant(
            $tenantId,
            (int) $request->get('per_page', 15),
            (int) $request->get('page', 1)
        );
        return response()->json($result);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(new UserResource($this->getService->findById($id)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = CreateUserData::fromArray($request->all());
        $user = $this->createService->execute($data);
        return response()->json(new UserResource($user), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = UpdateUserData::fromArray($request->all());
        $user = $this->updateService->execute($id, $data);
        return response()->json(new UserResource($user));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteService->execute($id);
        return response()->json(null, 204);
    }
}
