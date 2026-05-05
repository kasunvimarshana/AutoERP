<?php

declare(strict_types=1);

namespace Modules\Notifications\Domain\ValueObjects;

enum NotificationChannel: string
{
    case InApp = 'in_app';
    case Email = 'email';
    case Sms   = 'sms';
    case Push  = 'push';
}
