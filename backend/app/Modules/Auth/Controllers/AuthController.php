<?php
namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();

        if (!$user->is_active) {
            return response()->json(['message' => 'Account is inactive'], 403);
        }

        $token = $user->createToken('auth_token', $this->getScopes($user))->accessToken;

        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load(['roles', 'permissions', 'tenant']),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->token()->revoke();
        return response()->json(['success' => true, 'message' => 'Logged out successfully']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()->load(['roles', 'permissions', 'tenant']),
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->token()->revoke();
        $token = $user->createToken('auth_token', $this->getScopes($user))->accessToken;

        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    private function getScopes(User $user): array
    {
        $scopes = [];
        foreach ($user->getAllPermissions() as $permission) {
            $scopes[] = $permission->name;
        }
        return $scopes;
    }

    public function ssoCallback(Request $request): JsonResponse
    {
        $token = $request->input('sso_token');

        if (!$token) {
            return response()->json(['message' => 'SSO token required'], 400);
        }

        $user = User::where('email', $request->input('email'))->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $accessToken = $user->createToken('sso_token', $this->getScopes($user))->accessToken;

        return response()->json([
            'success' => true,
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'user' => $user->load(['roles', 'permissions', 'tenant']),
        ]);
    }
}
