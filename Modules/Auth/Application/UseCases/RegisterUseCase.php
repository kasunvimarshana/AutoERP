<?php
namespace Modules\Auth\Application\UseCases;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Domain\Contracts\AuthServiceInterface;
use Modules\Auth\Domain\ValueObjects\AuthToken;
use Modules\Auth\Domain\ValueObjects\LoginCredentials;
use Modules\User\Infrastructure\Models\UserModel;
class RegisterUseCase
{
    public function __construct(private AuthServiceInterface $authService) {}
    public function execute(array $data): AuthToken
    {
        return DB::transaction(function () use ($data) {
            $user = UserModel::create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'tenant_id' => $data['tenant_id'] ?? null,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'status' => 'active',
            ]);
            $credentials = new LoginCredentials(
                email: $data['email'],
                password: $data['password'],
                deviceName: $data['device_name'] ?? 'web',
            );
            return $this->authService->login($credentials);
        });
    }
}
