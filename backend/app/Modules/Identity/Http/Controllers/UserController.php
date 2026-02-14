<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Core\BaseController;
use App\Modules\Identity\Services\UserService;
use App\Modules\Identity\Requests\CreateUserRequest;
use App\Modules\Identity\Requests\UpdateUserRequest;
use App\Modules\Identity\Resources\UserResource;
use Illuminate\Http\JsonResponse;

/**
 * User Controller
 * 
 * Handles CRUD operations for user management
 * 
 * @package App\Modules\Identity\Http\Controllers
 */
class UserController extends BaseController
{
    protected UserService $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of users
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $users = $this->service->getAllUsers();
        return $this->collection(UserResource::collection($users));
    }

    /**
     * Store a newly created user
     *
     * @param CreateUserRequest $request
     * @return JsonResponse
     */
    public function store(CreateUserRequest $request): JsonResponse
    {
        $user = $this->service->createUser($request->validated());
        return $this->created(new UserResource($user));
    }

    /**
     * Display the specified user
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $user = $this->service->getUser($id);
        
        if (!$user) {
            return $this->notFound();
        }
        
        return $this->resource(new UserResource($user));
    }

    /**
     * Update the specified user
     *
     * @param UpdateUserRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $result = $this->service->updateUser($id, $request->validated());
        
        if (!$result) {
            return $this->notFound();
        }
        
        return $this->success(null, 'User updated successfully');
    }

    /**
     * Remove the specified user
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->deleteUser($id);
        
        if (!$result) {
            return $this->notFound();
        }
        
        return $this->noContent();
    }
}
