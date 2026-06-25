<?php

declare(strict_types=1);

namespace Modules\Auth\Constants;

final class RegistrationInvitationStatus
{
    public const PENDING = 'pending';
    public const ACCEPTED = 'accepted';
    public const REVOKED = 'revoked';
    public const EXPIRED = 'expired';

    private function __construct() {}
}
