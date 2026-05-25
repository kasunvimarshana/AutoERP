<?php

declare(strict_types=1);

namespace Modules\SystemUser\Domain\Constants;

final class SystemUserErrorCode
{
    public const NOT_FOUND = 'SYSTEM_USER_NOT_FOUND';
    public const INVALID_VALUE = 'SYSTEM_USER_INVALID_VALUE';
    public const CONFLICT = 'SYSTEM_USER_CONFLICT';

    private function __construct()
    {
    }
}
