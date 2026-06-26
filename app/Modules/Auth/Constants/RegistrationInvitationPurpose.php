<?php

declare(strict_types=1);

namespace Modules\Auth\Constants;

final class RegistrationInvitationPurpose
{
    public const INITIAL_ADMINISTRATOR = 'initial_administrator';

    public const USER_INVITATION = 'user_invitation';

    private function __construct() {}
}
