<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Modules\Auth\Constants\AuthErrorCode;
use Modules\Auth\Contracts\Providers\AuthProviderRegistryInterface;
use Modules\Auth\DTOs\AuthorizeClientData;
use Modules\Auth\DTOs\ExchangeAuthorizationCodeData;
use Modules\Auth\DTOs\LinkExternalIdentityData;
use Modules\Auth\DTOs\LoginData;
use Modules\Auth\DTOs\LogoutData;
use Modules\Auth\DTOs\RegistrationData;
use Modules\Auth\DTOs\TokenIssueData;
use Modules\Auth\DTOs\TokenRefreshData;
use Modules\Auth\DTOs\UnlinkExternalIdentityData;
use Modules\Auth\DTOs\VerificationChallengeRequestData;
use Modules\Auth\DTOs\VerificationChallengeVerifyData;
use Modules\Auth\Repositories\AuthIdentityRepositoryInterface;
use Modules\Auth\Repositories\AuthLoginAttemptRepositoryInterface;
use Modules\Auth\Repositories\AuthProviderRepositoryInterface;
use Modules\Auth\Services\Contracts\AuthDomainServiceInterface;
use Modules\Auth\Services\Registration\RegistrationInvitationService;
use Modules\Auth\Services\Registration\RegistrationPolicyService;
use Modules\Core\Contracts\ErrorNormalizerInterface;
use Modules\Core\Contracts\TransactionManagerInterface;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\OrganizationUnit\Repositories\OrganizationUnitRepositoryInterface;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\User\Repositories\UserOrganizationUnitRepositoryInterface;
use Modules\Auth\Services\Credentials\PasswordCredentialService;
use Modules\User\Contracts\TenantUserRegistrationInterface;
use Throwable;

final class AuthWorkflowService
{
    public function __construct(
        private readonly AuthProviderRegistryInterface $registry,
        private readonly AuthProviderRepositoryInterface $providers,
        private readonly AuthIdentityRepositoryInterface $identities,
        private readonly AuthLoginAttemptRepositoryInterface $loginAttempts,
        private readonly AuthDomainServiceInterface $domain,
        private readonly TenantUserRegistrationInterface $userRegistration,
        private readonly PasswordCredentialService $passwordCredentials,
        private readonly TransactionManagerInterface $transactions,
        private readonly ErrorNormalizerInterface $errorNormalizer,
        private readonly TenantRepositoryInterface $tenants,
        private readonly OrganizationUnitRepositoryInterface $organizationUnits,
        private readonly UserOrganizationUnitRepositoryInterface $userOrganizationUnits,
        private readonly RegistrationPolicyService $registrationPolicy,
        private readonly RegistrationInvitationService $registrationInvitations,
    ) {}

    public function login(LoginData $data): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($data): Result {
                if ($data->tenantId === null) {
                    return $this->failure(
                        AuthErrorCode::TENANT_RESOLUTION_FAILED,
                        'Tenant could not be resolved for this domain.',
                    );
                }

                if ($this->isLockedOut($data)) {
                    return $this->failure(AuthErrorCode::INVALID_CREDENTIALS, 'Credentials are invalid.');
                }

                $provider = $this->registry->authenticationProvider($data->tenantId, $data->providerKey);
                if ($provider === null) {
                    return $this->failure(AuthErrorCode::PROVIDER_NOT_FOUND, 'Auth provider is not available.');
                }

                $context = $provider->authenticate($data);
                if ($context === null) {
                    $this->recordAttempt($data, false, AuthErrorCode::INVALID_CREDENTIALS, null, null, null);

                    return $this->failure(AuthErrorCode::INVALID_CREDENTIALS, 'Credentials are invalid.');
                }

                $user = $context['user'];
                $providerRecord = $context['provider'];
                $identity = $context['identity'] ?? null;
                if (! $this->isActiveUser($user)) {
                    $this->recordAttempt($data, false, AuthErrorCode::USER_INACTIVE, null, null, (int) $user['id']);

                    return $this->failure(AuthErrorCode::USER_INACTIVE, 'User account is inactive.');
                }

                $organizationUnitId = $this->resolveUserOrganizationUnitId(
                    $data->tenantId,
                    (int) $user['id'],
                    $data->organizationUnitId,
                );
                if ($organizationUnitId instanceof Result) {
                    return $organizationUnitId;
                }

                $session = $this->registry->sessionProvider()->create([
                    'tenant_id' => $data->tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'provider_id' => $providerRecord['id'] ?? null,
                    'identity_id' => $identity['id'] ?? null,
                    'user_id' => $user['id'],
                    'ip_address' => $data->ipAddress,
                    'user_agent' => $data->userAgent,
                    'device_name' => $data->deviceName,
                    'metadata' => $data->metadata,
                ]);

                $tokenPair = $this->registry->tokenProvider()->issue(TokenIssueData::fromArray([
                    'tenant_id' => $data->tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'provider_id' => $providerRecord['id'] ?? null,
                    'identity_id' => $identity['id'] ?? null,
                    'session_id' => $session['id'] ?? null,
                    'tenant_user_id' => $user['id'],
                    'grant_type' => 'password',
                    'scopes' => [],
                ]));

                $this->recordAttempt(
                    $data,
                    true,
                    null,
                    isset($providerRecord['id']) ? (int) $providerRecord['id'] : null,
                    isset($identity['id']) ? (int) $identity['id'] : null,
                    (int) $user['id'],
                    $organizationUnitId,
                );

                $this->clearRecentFailures($data);

                return Result::success([
                    'provider' => $providerRecord,
                    'identity' => $identity,
                    'user' => $user,
                    'tenant' => $this->tenantSummary($data->tenantId),
                    'organization_unit' => $this->organizationUnitSummary($organizationUnitId),
                    'session' => $session,
                    'tokens' => $tokenPair,
                ]);
            });
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function logout(LogoutData $data): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($data): Result {
                if ($data->accessToken !== null) {
                    $this->registry->tokenProvider()->revokeAccessToken($data->accessToken, $data->tenantId);
                }

                if ($data->sessionId !== null) {
                    $sessionTenantId = $this->resolveSessionTenantId($data->sessionId, $data->tenantId);
                    $this->registry->tokenProvider()->revokeSessionTokens($data->sessionId, $sessionTenantId);
                    $this->registry->sessionProvider()->revoke($data->sessionId, $sessionTenantId);
                }

                return Result::success(true);
            });
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function register(RegistrationData $data): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($data): Result {
                $authorization = $this->registrationPolicy->authorize($data);
                if ($authorization->isFailure()) {
                    return Result::failure($authorization->errorOrFail());
                }
                if ($data->tenantId === null) {
                    return $this->failure(AuthErrorCode::TENANT_RESOLUTION_FAILED, 'A resolved tenant is required.');
                }

                $invitation = $authorization->valueOrFail();
                if (! $invitation instanceof \Modules\Core\DTOs\DataRecord) {
                    return $this->failure(
                        AuthErrorCode::INVITATION_INVALID,
                        'Registration requires an invitation tied to an account.',
                    );
                }

                $provider = $this->providers->findActiveByKey($data->tenantId, $data->providerKey);
                if ($provider === null || $data->providerKey !== 'internal') {
                    return $this->failure(
                        AuthErrorCode::PROVIDER_NOT_FOUND,
                        'Password registration requires the active internal authentication provider.',
                    );
                }

                $targetUserId = is_numeric($invitation->get('user_id'))
                    ? (int) $invitation->get('user_id')
                    : null;
                $organizationUnitId = is_numeric($invitation->get('organization_unit_id'))
                    ? (int) $invitation->get('organization_unit_id')
                    : null;
                $roleId = is_numeric($invitation->get('role_id'))
                    ? (int) $invitation->get('role_id')
                    : null;

                $existingIdentity = $this->identities->findByProviderAndSubject(
                    $data->tenantId,
                    (int) $provider->id(),
                    strtolower($data->email),
                );
                if ($existingIdentity !== null
                    && ($targetUserId === null || (int) $existingIdentity->get('user_id') !== $targetUserId)
                ) {
                    return $this->failure(
                        AuthErrorCode::INVITATION_INVALID,
                        'The invitation email is already linked to another account.',
                    );
                }

                $userId = $this->userRegistration->prepareFromInvitation(
                    $data->tenantId,
                    $targetUserId,
                    $organizationUnitId,
                    $roleId,
                    $data->firstName,
                    $data->lastName,
                    $data->email,
                );

                $this->passwordCredentials->setTenantUserPassword($data->tenantId, $userId, $data->password);

                if ($existingIdentity === null) {
                    $this->identities->create([
                        'tenant_id' => $data->tenantId,
                        'organization_unit_id' => $organizationUnitId,
                        'provider_id' => (int) $provider->id(),
                        'user_id' => $userId,
                        'provider_user_key' => strtolower($data->email),
                        'status' => 'active',
                        'is_primary' => true,
                        'metadata' => $data->metadata,
                        'row_version' => 1,
                    ]);
                }

                $this->registrationInvitations->accept(
                    $data->tenantId,
                    (int) $invitation->id(),
                    $userId,
                    (int) $invitation->require('row_version'),
                );

                return Result::success(
                    $this->userRegistration->activateAfterCredentialSetup($data->tenantId, $userId),
                );
            });
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function issueToken(TokenIssueData $data): Result
    {
        try {
            return $this->transactions->runInTransaction(
                fn (): Result => Result::success($this->registry->tokenProvider()->issue($data)),
            );
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function refreshToken(TokenRefreshData $data): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($data): Result {
                $tokens = $this->registry->tokenProvider()->refresh($data);
                if ($tokens === null) {
                    return $this->failure(AuthErrorCode::TOKEN_INVALID, 'Refresh token is invalid or expired.');
                }

                return Result::success($tokens);
            });
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function revokeSession(int|string $sessionId, ?int $tenantId = null): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($sessionId, $tenantId): Result {
                $sessionTenantId = $this->resolveSessionTenantId($sessionId, $tenantId);
                $this->registry->tokenProvider()->revokeSessionTokens((int) $sessionId, $sessionTenantId);
                $revoked = $this->registry->sessionProvider()->revoke($sessionId, $sessionTenantId);
                if (! $revoked) {
                    return $this->failure(AuthErrorCode::SESSION_NOT_FOUND, 'Session not found.');
                }

                return Result::success(true);
            });
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function listSessions(int $userId, ?int $tenantId = null): Result
    {
        try {
            return Result::success($this->registry->sessionProvider()->listForUser($userId, $tenantId));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function validateToken(string $accessToken, ?int $tenantId = null): Result
    {
        try {
            $token = $this->registry->tokenProvider()->validate($accessToken, $tenantId);
            if ($token === null) {
                return $this->failure(AuthErrorCode::TOKEN_INVALID, 'Access token is invalid.');
            }

            return Result::success($token);
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function requestVerificationChallenge(VerificationChallengeRequestData $data): Result
    {
        try {
            return $this->transactions->runInTransaction(
                fn (): Result => Result::success($this->registry->verificationProvider()->requestChallenge($data)),
            );
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function verifyChallenge(VerificationChallengeVerifyData $data): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($data): Result {
                $isValid = $this->registry->verificationProvider()->verifyChallenge($data);
                if (! $isValid) {
                    return $this->failure(AuthErrorCode::VERIFICATION_FAILED, 'Verification challenge failed.');
                }

                return Result::success(true);
            });
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function authorizeClient(AuthorizeClientData $data): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($data): Result {
                $authorizationCode = $this->registry->ssoProvider()->authorizeClient($data);
                if ($authorizationCode === null) {
                    return $this->failure(AuthErrorCode::CLIENT_NOT_ALLOWED, 'Client authorization failed.');
                }

                return Result::success($authorizationCode);
            });
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function exchangeAuthorizationCode(ExchangeAuthorizationCodeData $data): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($data): Result {
                $context = $this->registry->ssoProvider()->exchangeAuthorizationCode($data);
                if ($context === null) {
                    return $this->failure(
                        AuthErrorCode::AUTHORIZATION_CODE_INVALID,
                        'Authorization code is invalid or expired.',
                    );
                }

                $tokenPair = $this->registry->tokenProvider()->issue(TokenIssueData::fromArray([
                    'tenant_id' => $context['tenant_id'] ?? $data->tenantId,
                    'organization_unit_id' => $context['organization_unit_id'] ?? null,
                    'provider_id' => $context['provider_id'] ?? null,
                    'client_id' => $context['client_id'] ?? null,
                    'identity_id' => $context['identity_id'] ?? null,
                    'session_id' => $context['session_id'] ?? null,
                    'tenant_user_id' => $context['user_id'] ?? null,
                    'grant_type' => 'authorization_code',
                    'scopes' => $context['scopes'] ?? [],
                    'access_token_ttl_seconds' => $data->accessTokenTtlSeconds,
                    'refresh_token_ttl_seconds' => $data->refreshTokenTtlSeconds,
                    'metadata' => [
                        'authorization_code_id' => $context['authorization_code_id'] ?? null,
                    ],
                ]));

                return Result::success($tokenPair);
            });
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function linkExternalIdentity(LinkExternalIdentityData $data): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($data): Result {
                $provider = $this->providers->findActiveByKey($data->tenantId, trim(strtolower($data->providerKey)));
                if ($provider === null) {
                    return $this->failure(AuthErrorCode::PROVIDER_NOT_FOUND, 'Auth provider is not available.');
                }

                $providerId = (int) $provider->id();
                $subject = trim(strtolower($data->providerUserKey));
                if ($subject === '' || $data->userId < 1) {
                    return $this->failure(AuthErrorCode::INVALID_CREDENTIALS, 'Identity mapping input is invalid.');
                }

                $existing = $this->identities->findByProviderAndSubject($data->tenantId, $providerId, $subject);
                if ($existing !== null) {
                    if ((int) $existing->get('user_id') !== $data->userId) {
                        return $this->failure(
                            AuthErrorCode::TENANT_MISMATCH,
                            'Identity already linked to another user.',
                        );
                    }

                    $updated = $this->identities->update($existing->id(), [
                        'organization_unit_id' => $data->organizationUnitId,
                        'is_primary' => $data->isPrimary,
                        'claims' => $data->claims,
                        'metadata' => $data->metadata,
                        'status' => 'active',
                        'row_version' => ((int) $existing->get('row_version', 1)) + 1,
                    ]);

                    return Result::success($updated);
                }

                $created = $this->identities->create([
                    'tenant_id' => $data->tenantId,
                    'organization_unit_id' => $data->organizationUnitId,
                    'provider_id' => $providerId,
                    'user_id' => $data->userId,
                    'provider_user_key' => $subject,
                    'status' => 'active',
                    'is_primary' => $data->isPrimary,
                    'claims' => $data->claims,
                    'metadata' => $data->metadata,
                    'row_version' => 1,
                ]);

                return Result::success($created);
            });
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function unlinkExternalIdentity(UnlinkExternalIdentityData $data): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($data): Result {
                $provider = $this->providers->findActiveByKey($data->tenantId, trim(strtolower($data->providerKey)));
                if ($provider === null) {
                    return $this->failure(AuthErrorCode::PROVIDER_NOT_FOUND, 'Auth provider is not available.');
                }

                $providerId = (int) $provider->id();
                $subject = trim(strtolower($data->providerUserKey));
                $existing = $this->identities->findByProviderAndSubject($data->tenantId, $providerId, $subject);
                if ($existing === null || (int) $existing->get('user_id') !== $data->userId) {
                    return $this->failure(AuthErrorCode::UNAUTHORIZED_ACCESS, 'Identity link not found.');
                }

                $this->identities->delete($existing->id());

                return Result::success(true);
            });
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    private function failure(string $code, string $message): Result
    {
        return Result::failure(new Error($code, $message));
    }

    /**
     * @param  array<string,mixed>  $user
     */
    private function isActiveUser(array $user): bool
    {
        return strtolower(trim((string) ($user['status'] ?? ''))) === 'active';
    }

    private function resolveUserOrganizationUnitId(
        int $tenantId,
        int $userId,
        ?int $requestedOrganizationUnitId,
    ): int|Result|null {
        if ($requestedOrganizationUnitId !== null) {
            if (! $this->userOrganizationUnits->existsForTenantUserAndOrganizationUnit(
                $tenantId,
                $userId,
                $requestedOrganizationUnitId,
            ) || ! $this->isActiveOrganizationUnit($tenantId, $requestedOrganizationUnitId)) {
                return $this->failure(
                    AuthErrorCode::ORGANIZATION_UNIT_RESOLUTION_FAILED,
                    'Organization unit is not available for this user.',
                );
            }

            return $requestedOrganizationUnitId;
        }

        $assignment = $this->userOrganizationUnits->findDefaultForTenantAndUser($tenantId, $userId)
            ?? $this->userOrganizationUnits->firstActiveForTenantAndUser($tenantId, $userId);
        if ($assignment === null) {
            return null;
        }

        $organizationUnitId = $this->toNullableInt($assignment->get('organization_unit_id'));
        if ($organizationUnitId === null || ! $this->isActiveOrganizationUnit($tenantId, $organizationUnitId)) {
            return $this->failure(
                AuthErrorCode::ORGANIZATION_UNIT_RESOLUTION_FAILED,
                'The user default organization unit is not active.',
            );
        }

        return $organizationUnitId;
    }

    private function isActiveOrganizationUnit(int $tenantId, int $organizationUnitId): bool
    {
        $organizationUnit = $this->organizationUnits->findById($organizationUnitId);
        if ($organizationUnit === null || (int) $organizationUnit->get('tenant_id', 0) !== $tenantId) {
            return false;
        }

        return filter_var($organizationUnit->get('is_active', false), FILTER_VALIDATE_BOOL);
    }

    private function tenantSummary(?int $tenantId): ?array
    {
        if ($tenantId === null) {
            return null;
        }

        $tenant = $this->tenants->findById($tenantId);
        if ($tenant === null) {
            return ['id' => $tenantId, 'name' => null];
        }

        return [
            'id' => (int) $tenant->id(),
            'code' => $this->nullableString($tenant->get('code')),
            'name' => $this->nullableString($tenant->get('name')),
        ];
    }

    private function organizationUnitSummary(?int $organizationUnitId): ?array
    {
        if ($organizationUnitId === null) {
            return null;
        }

        $organizationUnit = $this->organizationUnits->findById($organizationUnitId);
        if ($organizationUnit === null) {
            return ['id' => $organizationUnitId, 'name' => null];
        }

        return [
            'id' => (int) $organizationUnit->id(),
            'code' => $this->nullableString($organizationUnit->get('code')),
            'name' => $this->nullableString($organizationUnit->get('name')),
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }

    private function fromThrowable(Throwable $throwable): Result
    {
        return Result::failure($this->errorNormalizer->normalize(
            $throwable,
            AuthErrorCode::UNAUTHORIZED_ACCESS,
            ['operation' => 'auth.workflow'],
        ));
    }

    private function recordAttempt(
        LoginData $data,
        bool $wasSuccessful,
        ?string $failureCode,
        ?int $providerId,
        ?int $identityId,
        ?int $userId,
        ?int $effectiveOrganizationUnitId = null,
    ): void {
        $this->loginAttempts->create([
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $effectiveOrganizationUnitId ?? $data->organizationUnitId,
            'provider_id' => $providerId,
            'identity_id' => $identityId,
            'user_id' => $userId,
            'login_identifier' => strtolower($data->loginIdentifier),
            'was_successful' => $wasSuccessful,
            'failure_code' => $failureCode,
            'ip_address' => $data->ipAddress,
            'user_agent' => $data->userAgent,
            'attempt_type' => 'password',
            'attempted_at' => now(),
            'row_version' => 1,
            'metadata' => $this->domain->normalizeMetadata($data->metadata),
        ]);
    }

    private function resolveSessionTenantId(int|string $sessionId, ?int $tenantId): ?int
    {
        if ($tenantId !== null) {
            return $tenantId;
        }

        return $this->registry
            ->sessionProvider()
            ->tenantIdForSession($sessionId);
    }

    private function isLockedOut(LoginData $data): bool
    {
        $maxAttempts = max(
            1,
            $this->readModuleAuthIntConfig('max_login_attempts', 5),
        );
        $windowSeconds = max(
            30,
            $this->readModuleAuthIntConfig('login_attempt_window_seconds', 900),
        );
        $since = now()->subSeconds($windowSeconds);

        $recentFailures = $this->loginAttempts->countRecentFailures(
            $data->tenantId,
            $data->loginIdentifier,
            $data->ipAddress,
            $since,
        );

        return $recentFailures >= $maxAttempts;
    }

    private function clearRecentFailures(LoginData $data): void
    {
        $windowSeconds = max(
            30,
            $this->readModuleAuthIntConfig('login_attempt_window_seconds', 900),
        );
        $since = now()->subSeconds($windowSeconds);

        $this->loginAttempts->clearRecentFailures(
            $data->tenantId,
            $data->loginIdentifier,
            $data->ipAddress,
            $since,
        );
    }

    private function readModuleAuthIntConfig(string $key, int $default): int
    {
        try {
            return (int) config('module-auth.'.$key, $default);
        } catch (Throwable) {
            return $default;
        }
    }
}
