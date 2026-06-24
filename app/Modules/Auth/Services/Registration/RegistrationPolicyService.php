<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Registration;

use Modules\Auth\Constants\AuthErrorCode;
use Modules\Auth\DTOs\RegistrationData;
use Modules\Configuration\Contracts\ConfigurationResolverInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;

final class RegistrationPolicyService
{
    private const MODE_DISABLED = 'disabled';
    private const MODE_INVITE_ONLY = 'invite_only';
    private const MODE_APPROVED_DOMAINS = 'approved_domains';
    private const MODE_OPEN = 'open';

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
        if ($mode === self::MODE_DISABLED) {
            return Result::failure(new Error(
                AuthErrorCode::REGISTRATION_DISABLED,
                'Registration is disabled for this workspace.',
            ));
        }

        $invitation = $this->invitations->findValid($tenantId, $data->email, $data->invitationToken);
        if ($mode === self::MODE_INVITE_ONLY) {
            return $invitation !== null
                ? Result::success($invitation)
                : Result::failure(new Error(
                    AuthErrorCode::INVITATION_INVALID,
                    'A valid registration invitation is required.',
                ));
        }

        if ($mode === self::MODE_APPROVED_DOMAINS && ! $this->emailDomainAllowed($tenantId, $data->email)) {
            return Result::failure(new Error(
                AuthErrorCode::REGISTRATION_DOMAIN_NOT_ALLOWED,
                'This email domain is not approved for workspace registration.',
            ));
        }

        if ($mode !== self::MODE_OPEN && $mode !== self::MODE_APPROVED_DOMAINS) {
            return Result::failure(new Error(
                AuthErrorCode::REGISTRATION_DISABLED,
                'The workspace registration policy is invalid.',
            ));
        }

        return Result::success($invitation);
    }

    private function emailDomainAllowed(int $tenantId, string $email): bool
    {
        $separator = strrpos($email, '@');
        $domain = $separator === false ? '' : strtolower(substr($email, $separator + 1));
        $configured = $this->configuration->value('auth.registration_approved_domains', $tenantId);
        $domains = is_array($configured) ? $configured : [];
        $normalized = array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): string => is_string($value)
                ? strtolower(rtrim(trim($value), '.'))
                : '',
            $domains,
        ))));

        return $domain !== '' && in_array($domain, $normalized, true);
    }
}
