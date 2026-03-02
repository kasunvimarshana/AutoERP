<?php

declare(strict_types=1);

namespace Modules\Auth\Interfaces\Http\Controllers;

use App\Shared\Abstractions\BaseController;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Application\Commands\LoginCommand;
use Modules\Auth\Application\Commands\LogoutCommand;
use Modules\Auth\Application\Commands\RegisterUserCommand;
use Modules\Auth\Application\Services\AuthService;
use Modules\Auth\Interfaces\Http\Requests\LoginRequest;
use Modules\Auth\Interfaces\Http\Requests\RegisterRequest;
use Modules\Auth\Interfaces\Http\Resources\UserResource;

class AuthController extends BaseController
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $user = $this->authService->registerUser(new RegisterUserCommand(
                tenantId: (int) $request->validated('tenant_id'),
                name: $request->validated('name'),
                email: $request->validated('email'),
                password: $request->validated('password'),
            ));

            return $this->success(
                data: (new UserResource($user))->resolve(),
                message: 'User registered successfully',
                status: 201,
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login(new LoginCommand(
                tenantId: (int) $request->validated('tenant_id'),
                email: $request->validated('email'),
                password: $request->validated('password'),
                deviceName: $request->validated('device_name', 'api'),
            ));

            return $this->success(
                data: [
                    'user' => (new UserResource($result['user']))->resolve(),
                    'token' => $result['token'],
                ],
                message: 'Login successful',
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 401);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var \Modules\Auth\Infrastructure\Models\UserModel|null $user */
        $user = auth()->user();

        if ($user !== null) {
            $this->authService->logout(new LogoutCommand(
                userId: $user->id,
                tenantId: $user->tenant_id,
                bearerToken: $request->bearerToken(),
            ));
        }

        return $this->success(message: 'Logged out successfully');
    }

    public function me(): JsonResponse
    {
        /** @var \Modules\Auth\Infrastructure\Models\UserModel|null $authUser */
        $authUser = auth()->user();

        if ($authUser === null) {
            return $this->error('Unauthenticated', status: 401);
        }

        $user = $this->authService->findUserById($authUser->id, $authUser->tenant_id);

        if ($user === null) {
            return $this->error('User not found', status: 404);
        }

        return $this->success(
            data: (new UserResource($user))->resolve(),
            message: 'User retrieved successfully',
        );
    }
}
