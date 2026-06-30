<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Registration;

final class RegistrationInvitationTokenFormat
{
    public const ENCODED_LENGTH = 64;
    public const VALIDATION_PATTERN = '/\A[a-f0-9]{64}\z/';

    private function __construct() {}
}
