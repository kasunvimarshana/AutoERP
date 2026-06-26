<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Registration;

use Modules\Auth\Constants\AuthErrorCode;
use Modules\Auth\Constants\RegistrationMode;
use Modules\Auth\DTOs\RegistrationData;
use Modules\Configuration\Contracts\ConfigurationResolverInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;

final class RegistrationPolicyService
{
    public function __construct(
        private readonly ConfigurationResolverInterface $configuration,
        private readonly RegistrationInvitationService $invitations,
    ) {}

    /** @return Result<DataRecord|null> */
    public function authorize(RegistrationData $data): Result
    {
        $tenantId = (int) ($data->tenantId ?? 0);
        if ($tenantId < 1) {
            return Result::failure(new Error(
                AuthErrorCode::TENANT_RESOLUTION_FAILED,
                'A resolved tenant is required for registration.',
            ));
        }

        $mode = (string) $this->configuration->value('auth.registration_mode', $tenantId);
        if ($mode === RegistrationMode::DISABLED) {
            return Result::failure(new Error(
                AuthErrorCode::REGISTRATION_DISABLED,
                'Registration is disabled for this workspace.',
            ));
        }

        if ($mode !== RegistrationMode::INVITE_ONLY) {
            return Result::failure(new Error(
                AuthErrorCode::REGISTRATION_DISABLED,
                'The workspace registration policy is invalid.',
            ));
        }

        $invitation = $this->invitations->findValid($tenantId, $data->email, $data->invitationToken);

        return $invitation !== null
            ? Result::success($invitation)
            : Result::failure(new Error(
                AuthErrorCode::INVITATION_INVALID,
                'A valid registration invitation is required.',
            ));
    }
}
