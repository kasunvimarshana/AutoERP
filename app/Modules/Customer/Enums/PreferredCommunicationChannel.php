<?php

declare(strict_types=1);

namespace Modules\Customer\Enums;

enum PreferredCommunicationChannel: string
{
    case Email = 'email';
    case Phone = 'phone';
    case Mobile = 'mobile';
    case Sms = 'sms';
    case WhatsApp = 'whatsapp';
    case Other = 'other';
}
