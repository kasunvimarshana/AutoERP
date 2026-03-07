<?php

namespace App\Modules\Auth\Services;

use App\Modules\Tenant\Models\Tenant;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function register(array $data): array
    {
        $tenant = null;

        if (!empty($data['tenant_name'])) {
            $tenant = Tenant::create([
                'name'      => $data['tenant_name'],
                'slug'      => Str::slug($data['tenant_name']) . '-' . Str::random(6),
                'is_active' => true,
                'plan'      => 'free',
            ]);
        }

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'role'      => 'admin',
            'tenant_id' => $tenant?->id,
        ]);

        $user->assignRole('admin');

        $token = $user->createToken('auth_token');

        return [
            'access_token' => $token->accessToken,
            'token_type'   => 'Bearer',
            'expires_at'   => $token->token->expires_at?->toIso8601String(),
            'user'         => $user,
        ];
    }

    public function login(array $credentials): array
    {
        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token');

        return [
            'access_token' => $token->accessToken,
            'token_type'   => 'Bearer',
            'expires_at'   => $token->token->expires_at?->toIso8601String(),
            'user'         => $user,
        ];
    }

    public function logout(User $user): void
    {
        $user->token()->revoke();
    }

    public function refreshToken(string $token): array
    {
        $user = Auth::guard('api')->user();

        $user->token()->revoke();
        $newToken = $user->createToken('auth_token');

        return [
            'access_token' => $newToken->accessToken,
            'token_type'   => 'Bearer',
            'expires_at'   => $newToken->token->expires_at?->toIso8601String(),
            'user'         => $user,
        ];
    }

    public function getCurrentUser(User $user): array
    {
        return [
            'user' => $user->load('roles', 'permissions'),
        ];
    }
}
