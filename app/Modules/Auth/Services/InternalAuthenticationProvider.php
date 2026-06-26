<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Modules\Auth\Contracts\Providers\AuthenticationProviderInterface;
use Modules\Auth\Contracts\Providers\IdentityProviderInterface;
use Modules\Auth\DTOs\LoginData;
use Modules\Auth\DTOs\RegistrationData;
use Modules\Auth\Repositories\AuthProviderRepositoryInterface;
use Modules\Auth\Services\Credentials\PasswordCredentialService;
use Modules\User\Repositories\UserOrganizationUnitRepositoryInterface;
use Modules\User\Repositories\UserRepositoryInterface;

final class InternalAuthenticationProvider implements AuthenticationProviderInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly AuthProviderRepositoryInterface $providers,
        private readonly IdentityProviderInterface $identities,
        private readonly UserOrganizationUnitRepositoryInterface $organizationUnitAssignments,
        private readonly PasswordCredentialService $credentials,
    ) {}

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
        if ($data->tenantId === null) {
            return null;
        }

        $provider = $this->providers->findActiveByKey($data->tenantId, $this->key());
        if ($provider === null) {
            return null;
        }

        $identifier = strtolower(trim($data->loginIdentifier));
        if ($identifier === '') {
            return null;
        }

        $user = $this->users->findByTenantAndLoginIdentifier($data->tenantId, $identifier);
        if ($user === null) {
            return null;
        }

        if (! $this->credentials->verifyTenantUser($data->tenantId, (int) $user->id(), $data->password)) {
            return null;
        }

        if ($data->organizationUnitId !== null && ! $this->organizationUnitAssignments
            ->existsForTenantUserAndOrganizationUnit(
                $data->tenantId,
                (int) $user->id(),
                $data->organizationUnitId,
            )) {
            return null;
        }

        $identity = $this->identities->findByUserAndProvider($data->tenantId, (int) $user->id(), (int) $provider->id());
        if ($identity === null) {
            $identity = $this->identities->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'provider_id' => (int) $provider->id(),
                'user_id' => (int) $user->id(),
                'provider_user_key' => (string) $user->get('email', $identifier),
                'status' => 'active',
                'is_primary' => true,
                'last_authenticated_at' => now(),
                'row_version' => 1,
                'metadata' => $data->metadata,
            ]);
        } else {
            $identity = $this->identities->update($identity->id(), [
                'last_authenticated_at' => now(),
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
