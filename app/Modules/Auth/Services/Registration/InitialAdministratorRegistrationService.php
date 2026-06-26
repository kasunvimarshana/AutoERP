<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Registration;

use Illuminate\Database\DatabaseManager;
use Modules\Auth\Constants\AuthErrorCode;
use Modules\Auth\Constants\RegistrationInvitationPurpose;
use Modules\Auth\Constants\RegistrationInvitationStatus;
use Modules\Auth\Enums\IdentityStatus;
use Modules\Auth\Enums\ProviderStatus;
use Modules\Auth\Models\AuthIdentityModel;
use Modules\Auth\Models\AuthProviderModel;
use Modules\Auth\Models\AuthRegistrationInvitationModel;
use Modules\Auth\Services\Credentials\PasswordCredentialService;
use Modules\Auth\Services\Security\OpaqueTokenCodec;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\User\Contracts\TenantUserRegistrationInterface;
use Throwable;

final readonly class InitialAdministratorRegistrationService
{
    public function __construct(
        private RegistrationInvitationService $invitations,
        private AuthRegistrationInvitationModel $invitationModel,
        private TenantUserRegistrationInterface $users,
        private PasswordCredentialService $credentials,
        private TenantExecutionContextInterface $executionContext,
        private DatabaseManager $database,
        private ClockInterface $clock,
        private OpaqueTokenCodec $tokens,
    ) {}

    public function inspect(string $token): Result
    {
        $invitation = $this->invitations->inspectInitialAdministratorToken($token);
        if ($invitation === null) {
            return $this->invalidInvitation();
        }

        return Result::success([
            'tenant_name' => $invitation['tenant_name'],
            'email' => $this->maskEmail($invitation['email']),
            'expires_at' => $invitation['expires_at'],
        ]);
    }

    public function register(string $token, string $firstName, ?string $lastName, string $password): Result
    {
        $token = trim($token);
        $digest = $this->tokens->digestArbitrary($token, 'registration-invitation');
        $locator = $this->executionContext->runAsControlPlane(function () use ($digest): ?array {
            $invitation = $this->invitationModel->newQueryWithoutScopes()
                ->where('token_hash', $digest)
                ->where('purpose', RegistrationInvitationPurpose::INITIAL_ADMINISTRATOR)
                ->where('status', RegistrationInvitationStatus::PENDING)
                ->where('expires_at', '>', $this->clock->now())
                ->first(['id', 'tenant_id']);

            return $invitation instanceof AuthRegistrationInvitationModel
                ? ['id' => (int) $invitation->getKey(), 'tenant_id' => (int) $invitation->getAttribute('tenant_id')]
                : null;
        });
        if ($locator === null) {
            return $this->invalidInvitation();
        }

        try {
            return $this->executionContext->runForTenant($locator['tenant_id'], fn (): Result => $this->database->transaction(
                function () use ($locator, $digest, $firstName, $lastName, $password): Result {
                    $invitation = $this->invitationModel->newQuery()
                        ->whereKey($locator['id'])
                        ->where('token_hash', $digest)
                        ->where('purpose', RegistrationInvitationPurpose::INITIAL_ADMINISTRATOR)
                        ->where('status', RegistrationInvitationStatus::PENDING)
                        ->where('expires_at', '>', $this->clock->now())
                        ->lockForUpdate()
                        ->first();
                    if (! $invitation instanceof AuthRegistrationInvitationModel) {
                        return $this->invalidInvitation();
                    }

                    $tenantId = (int) $invitation->getAttribute('tenant_id');
                    $userId = $this->users->prepareFromInvitation(
                        $tenantId,
                        $this->positiveInt($invitation->getAttribute('user_id')),
                        $this->positiveInt($invitation->getAttribute('organization_unit_id')),
                        $this->positiveInt($invitation->getAttribute('role_id')),
                        trim($firstName),
                        $this->nullable($lastName),
                        (string) $invitation->getAttribute('email'),
                    );

                    $provider = AuthProviderModel::query()->firstOrCreate(
                        ['tenant_id' => $tenantId, 'provider_key' => (string) config('module-auth.internal_provider_key', 'internal')],
                        [
                            'name' => 'Internal Password',
                            'driver' => 'internal_password',
                            'status' => ProviderStatus::ACTIVE->value,
                            'row_version' => 1,
                        ],
                    );
                    if ((string) $provider->getAttribute('status') !== ProviderStatus::ACTIVE->value) {
                        throw new \RuntimeException('The internal authentication provider is inactive.');
                    }

                    $email = mb_strtolower(trim((string) $invitation->getAttribute('email')));
                    $identity = AuthIdentityModel::query()
                        ->where('provider_id', $provider->getKey())
                        ->where('user_id', $userId)
                        ->lockForUpdate()
                        ->first();
                    if (! $identity instanceof AuthIdentityModel) {
                        $identity = AuthIdentityModel::query()->create([
                            'tenant_id' => $tenantId,
                            'provider_id' => (int) $provider->getKey(),
                            'user_id' => $userId,
                            'provider_user_key' => $email,
                            'status' => IdentityStatus::ACTIVE->value,
                            'primary_marker' => 'primary',
                            'verified_at' => $this->clock->now(),
                            'row_version' => 1,
                        ]);
                    } elseif ((string) $identity->getAttribute('status') !== IdentityStatus::ACTIVE->value) {
                        throw new \RuntimeException('The invited identity is inactive.');
                    }

                    $this->credentials->setTenantUserPassword($tenantId, $userId, $password);
                    $user = $this->users->activateAfterCredentialSetup($tenantId, $userId);
                    $this->invitations->accept(
                        $tenantId,
                        (int) $invitation->getKey(),
                        $userId,
                        (int) $invitation->getAttribute('row_version'),
                    );

                    return Result::success([
                        'user_id' => $userId,
                        'email' => $email,
                        'name' => trim((string) ($user['first_name'] ?? $firstName).' '.(string) ($user['last_name'] ?? $lastName)),
                        'status' => (string) ($user['status'] ?? 'active'),
                    ]);
                },
                3,
            ));
        } catch (Throwable $exception) {
            report($exception);
            return Result::failure(new Error(
                AuthErrorCode::REGISTRATION_DISABLED,
                'The administrator account could not be activated. Review the tenant foundation and try again.',
            ));
        }
    }

    private function invalidInvitation(): Result
    {
        return Result::failure(new Error(
            AuthErrorCode::INVITATION_INVALID,
            'The administrator invitation is invalid, expired, or no longer available.',
        ));
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        if ($local === '' || $domain === '') {
            return 'hidden';
        }
        return mb_substr($local, 0, 1).str_repeat('•', max(3, mb_strlen($local) - 1)).'@'.$domain;
    }

    private function positiveInt(mixed $value): ?int
    {
        if (! is_int($value) && ! ctype_digit((string) $value)) {
            return null;
        }
        $value = (int) $value;
        return $value > 0 ? $value : null;
    }

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}
