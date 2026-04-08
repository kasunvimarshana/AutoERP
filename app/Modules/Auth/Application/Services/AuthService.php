<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Application\Contracts\AuthServiceInterface;
use Modules\Auth\Application\DTOs\LoginData;
use Modules\Auth\Application\DTOs\RegisterUserData;
use Modules\Auth\Domain\Events\UserLoggedIn;
use Modules\Auth\Domain\Events\UserRegistered;
use Modules\Auth\Domain\Exceptions\InvalidCredentialsException;
use Modules\Auth\Domain\Exceptions\UserNotFoundException;
use Modules\Auth\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Modules\Auth\Domain\ValueObjects\UserStatus;

final class AuthService implements AuthServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function register(RegisterUserData $dto): array
    {
        $dto->validate($dto->toArray());

        return DB::transaction(function () use ($dto) {
            $user = $this->userRepository->create([
                'name'      => $dto->name,
                'email'     => $dto->email,
                'password'  => Hash::make($dto->password),
                'tenant_id' => $dto->tenant_id,
                'phone'     => $dto->phone,
                'locale'    => $dto->locale ?? 'en',
                'timezone'  => $dto->timezone ?? 'UTC',
                'status'    => UserStatus::ACTIVE,
            ]);

            $token = $user->createToken('auth_token')->accessToken;

            event(new UserRegistered((int) $user->id, (int) $user->tenant_id));

            return ['user' => $user, 'token' => $token];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function login(LoginData $dto): array
    {
        $dto->validate($dto->toArray());

        $user = $this->userRepository->findByEmail($dto->email);

        if (! $user) {
            throw new UserNotFoundException($dto->email);
        }

        if (! Hash::check($dto->password, $user->password)) {
            throw new InvalidCredentialsException();
        }

        if ($user->status === UserStatus::SUSPENDED || $user->status === UserStatus::INACTIVE) {
            throw new InvalidCredentialsException('Account is not active.');
        }

        $this->userRepository->update($user->id, [
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);

        $token = $user->createToken('auth_token')->accessToken;

        event(new UserLoggedIn((int) $user->id, (int) $user->tenant_id));

        return ['user' => $user->fresh(), 'token' => $token];
    }

    /**
     * {@inheritdoc}
     */
    public function logout(int $userId): void
    {
        $user = $this->userRepository->find($userId);

        if (! $user) {
            throw new UserNotFoundException((string) $userId);
        }

        $user->tokens()->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function refreshToken(string $refreshToken): array
    {
        // Passport handles token refresh via its built-in OAuth endpoint.
        // This method is provided for service-layer consistency.
        throw new \RuntimeException(
            'Use the Passport OAuth /oauth/token endpoint with grant_type=refresh_token.'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function me(int $userId): mixed
    {
        $user = $this->userRepository->find($userId);

        if (! $user) {
            throw new UserNotFoundException((string) $userId);
        }

        return $user;
    }
}
