<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Services;

use Modules\Auth\Application\Contracts\Providers\AuthenticationProviderInterface;
use Modules\Auth\Application\Contracts\Providers\IdentityProviderInterface;
use Modules\Auth\Application\DTOs\LoginData;
use Modules\Auth\Application\DTOs\RegistrationData;
use Modules\Auth\Application\Repositories\AuthProviderRepositoryInterface;
use Modules\Core\Application\Contracts\ClockInterface;
use Modules\Core\Application\Contracts\PasswordHasherInterface;
use Modules\User\Application\Repositories\UserRepositoryInterface;

final class InternalAuthenticationProvider implements AuthenticationProviderInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly AuthProviderRepositoryInterface $providers,
        private readonly IdentityProviderInterface $identities,
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly ClockInterface $clock,
    ) {
    }

    public function key(): string
    {
        return 'internal';
    }

    /**
     * @return array{
     *     user: array<string, mixed>,
     *     provider: array<string, mixed>,
     *     identity: array<string, mixed>|null
     * }|null
     */
    public function authenticate(LoginData $data): ?array
    {
        $provider = $this->providers->findActiveByKey($data->tenantId, $this->key());
        if ($provider === null) {
            return null;
        }

        $email = strtolower(trim($data->loginIdentifier));
        if ($email === '') {
            return null;
        }

        $user = $this->users->findByTenantAndEmail($data->tenantId, $email);
        if ($user === null) {
            return null;
        }

        $hashedPassword = (string) $user->get('password', '');
        if (! $this->passwordHasher->verify($data->password, $hashedPassword)) {
            return null;
        }

        $identity = $this->identities->findByUserAndProvider($data->tenantId, (int) $user->id(), (int) $provider->id());
        if ($identity === null) {
            $identity = $this->identities->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'provider_id' => (int) $provider->id(),
                'user_id' => (int) $user->id(),
                'provider_user_key' => $email,
                'status' => 'active',
                'is_primary' => true,
                'last_authenticated_at' => $this->clock->now(),
                'row_version' => 1,
                'metadata' => $data->metadata,
            ]);
        } else {
            $identity = $this->identities->update($identity->id(), [
                'last_authenticated_at' => $this->clock->now(),
                'row_version' => ((int) $identity->get('row_version', 1)) + 1,
            ]);
        }

        return [
            'provider' => $provider->toArray(),
            'user' => $user->toArray(),
            'identity' => $identity->toArray(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function register(RegistrationData $data): ?array
    {
        return null;
    }
}
