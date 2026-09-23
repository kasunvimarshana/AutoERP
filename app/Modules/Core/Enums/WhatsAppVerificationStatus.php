<?php

declare(strict_types=1);

namespace Modules\Core\Enums;

enum WhatsAppVerificationStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Failed = 'failed';
    case Expired = 'expired';
    case Superseded = 'superseded';
}
