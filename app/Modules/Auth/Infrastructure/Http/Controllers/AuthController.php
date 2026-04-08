<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\UserModel;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        /** @var UserModel|null $user */
        $user = UserModel::withoutGlobalScopes()
            ->where('email', $credentials['email'])
            ->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $token = $user->createToken('auth-token')->accessToken;

        return response()->json([
            'token_type'   => 'Bearer',
            'access_token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->token()->revoke();

        return response()->json(['message' => 'Successfully logged out.']);
    }

    public function refresh(Request $request): JsonResponse
    {
        /** @var UserModel $user */
        $user = $request->user();

        $user->token()->revoke();

        $token = $user->createToken('auth-token')->accessToken;

        return response()->json([
            'token_type'   => 'Bearer',
            'access_token' => $token,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var UserModel $user */
        $user = $request->user();

        return response()->json([
            'id'          => $user->id,
            'tenant_id'   => $user->tenant_id,
            'name'        => $user->name,
            'email'       => $user->email,
            'role'        => $user->role,
            'status'      => $user->status,
            'preferences' => $user->preferences ?? [],
            'created_at'  => $user->created_at?->toIso8601String(),
            'updated_at'  => $user->updated_at?->toIso8601String(),
        ]);
    }
}
