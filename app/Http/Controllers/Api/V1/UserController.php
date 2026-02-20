<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\CreateUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    public function index(Request $request): ResourceCollection
    {
        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);

        return UserResource::collection($this->userService->paginate($tenantId, $perPage));
    }

    public function store(CreateUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = $request->user()->tenant_id;

        $user = $this->userService->create($data);

        return (new UserResource($user))->response()->setStatusCode(201);
    }

    public function update(UpdateUserRequest $request, string $id): JsonResponse
    {
        $user = $this->userService->update($id, $request->validated());

        return (new UserResource($user))->response();
    }

    public function suspend(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('users.update'), 403);
        $user = $this->userService->suspend($id);

        return (new UserResource($user))->response();
    }

    public function activate(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('users.update'), 403);
        $user = $this->userService->activate($id);

        return (new UserResource($user))->response();
    }
}
