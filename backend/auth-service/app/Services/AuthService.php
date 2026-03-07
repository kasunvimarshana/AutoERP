<?php

namespace App\Services;

use App\DTOs\UserDTO;
use App\Models\Tenant;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // Find or create tenant
            $tenant = Tenant::where('slug', $data['tenant_slug'])->firstOrFail();

            app()->instance('current_tenant_id', $tenant->id);

            if ($this->userRepository->findByEmail($data['email'], $tenant->id)) {
                throw ValidationException::withMessages([
                    'email' => ['Email already exists for this tenant.'],
                ]);
            }

            $dto = UserDTO::fromArray([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'tenant_id' => $tenant->id,
                'roles' => $data['roles'] ?? ['staff'],
                'attributes' => $data['attributes'] ?? [],
            ]);

            $user = $this->userRepository->create($dto->toArray());
            $tokenData = $this->createToken($user, $tenant);

            return [
                'user' => $user,
                'tenant' => $tenant,
                'token' => $tokenData,
            ];
        });
    }

    public function login(array $credentials, int $tenantId): array
    {
        app()->instance('current_tenant_id', $tenantId);

        $user = $this->userRepository->findByEmail($credentials['email'], $tenantId);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Account is inactive.'],
            ]);
        }

        $tenant = $user->tenant;
        $tokenData = $this->createToken($user, $tenant);

        return [
            'user' => $user,
            'tenant' => $tenant,
            'token' => $tokenData,
        ];
    }

    public function logout(User $user): void
    {
        $user->token()->revoke();
    }

    public function introspect(string $token): array
    {
        // Find the access token
        $accessToken = \Laravel\Passport\Token::where('id', $this->getTokenId($token))
            ->with('user')
            ->first();

        if (! $accessToken || $accessToken->revoked) {
            return ['active' => false];
        }

        if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
            return ['active' => false];
        }

        $user = $accessToken->user;

        return [
            'active' => true,
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'email' => $user->email,
            'name' => $user->name,
            'roles' => $user->roles ?? [],
            'attributes' => $user->attributes ?? [],
            'expires_at' => $accessToken->expires_at?->toIso8601String(),
        ];
    }

    private function createToken(User $user, Tenant $tenant): array
    {
        $token = $user->createToken('auth_token', ['*']);

        return [
            'access_token' => $token->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => now()->addDays(15)->toIso8601String(),
            'tenant_id' => $tenant->id,
            'tenant_slug' => $tenant->slug,
        ];
    }

    private function getTokenId(string $bearerToken): string
    {
        // Extract the token ID from the JWT or personal access token
        $parts = explode('.', $bearerToken);
        if (count($parts) === 3) {
            // JWT format
            $payload = json_decode(base64_decode($parts[1]), true);

            return $payload['jti'] ?? '';
        }

        return '';
    }
}
