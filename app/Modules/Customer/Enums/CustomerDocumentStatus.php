<?php

declare(strict_types=1);

namespace Modules\Customer\Enums;

enum CustomerDocumentStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
    case Revoked = 'revoked';
    case Pending = 'pending';
}
