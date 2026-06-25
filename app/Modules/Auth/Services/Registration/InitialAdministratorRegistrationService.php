<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Registration;

use Modules\Auth\Constants\AuthErrorCode;
use Modules\Auth\DTOs\RegistrationData;
use Modules\Auth\Services\RegisterService;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;

final class InitialAdministratorRegistrationService
{
    public function __construct(
        private readonly RegistrationInvitationService $invitations,
        private readonly RegisterService $registration,
        private readonly TenantExecutionContextInterface $executionContext,
    ) {}

    public function inspect(string $token): Result
    {
        $invitation = $this->invitations->inspectInitialAdministratorToken($token);
        if ($invitation === null) {
            return Result::failure(new Error(
                AuthErrorCode::INVITATION_INVALID,
                'The administrator invitation is invalid, expired, or no longer available.',
            ));
        }

        return Result::success([
            'tenant_name' => $invitation['tenant_name'],
            'email' => $this->maskEmail($invitation['email']),
            'expires_at' => $invitation['expires_at'],
        ]);
    }

    public function register(string $token, string $firstName, ?string $lastName, string $password): Result
    {
        $invitation = $this->invitations->inspectInitialAdministratorToken($token);
        if ($invitation === null) {
            return Result::failure(new Error(
                AuthErrorCode::INVITATION_INVALID,
                'The administrator invitation is invalid, expired, or no longer available.',
            ));
        }

        return $this->executionContext->runForTenant(
            (int) $invitation['tenant_id'],
            fn (): Result => $this->registration->register(new RegistrationData(
                tenantId: (int) $invitation['tenant_id'],
                organizationUnitId: null,
                providerKey: 'internal',
                firstName: trim($firstName),
                lastName: $lastName === null || trim($lastName) === '' ? null : trim($lastName),
                email: (string) $invitation['email'],
                password: $password,
                invitationToken: trim($token),
                metadata: null,
            )),
        );
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        if ($local === '' || $domain === '') {
            return 'hidden';
        }

        $visible = mb_substr($local, 0, 1);

        return $visible.str_repeat('•', max(3, mb_strlen($local) - 1)).'@'.$domain;
    }
}
