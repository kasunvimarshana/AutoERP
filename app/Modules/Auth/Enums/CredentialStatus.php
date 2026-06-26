<?php

declare(strict_types=1);

namespace Modules\Auth\Enums;

enum CredentialStatus: string
{
    case ACTIVE = 'active';
    case REVOKED = 'revoked';
}
