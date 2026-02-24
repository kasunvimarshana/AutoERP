<?php
namespace Modules\Auth\Presentation\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\Application\UseCases\LoginUseCase;
use Modules\Auth\Application\UseCases\LogoutUseCase;
use Modules\Auth\Application\UseCases\RegisterUseCase;
use Modules\Auth\Presentation\Requests\LoginRequest;
use Modules\Auth\Presentation\Requests\RegisterRequest;
use Modules\Shared\Application\ResponseFormatter;
class AuthController extends Controller
{
    public function __construct(
        private LoginUseCase $loginUseCase,
        private LogoutUseCase $logoutUseCase,
        private RegisterUseCase $registerUseCase,
    ) {}
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $token = $this->loginUseCase->execute($request->validated());
            return ResponseFormatter::success($token->toArray(), 'Logged in.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseFormatter::error('Invalid credentials.', $e->errors(), 401);
        }
    }
    public function logout(): JsonResponse
    {
        $this->logoutUseCase->execute(['user_id' => auth()->id()]);
        return ResponseFormatter::success(null, 'Logged out.');
    }
    public function register(RegisterRequest $request): JsonResponse
    {
        $token = $this->registerUseCase->execute($request->validated());
        return ResponseFormatter::success($token->toArray(), 'Registered.', 201);
    }
    public function me(): JsonResponse
    {
        return ResponseFormatter::success(auth()->user());
    }
}
