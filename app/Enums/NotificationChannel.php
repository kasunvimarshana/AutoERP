<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case Email = 'email';
    case Sms = 'sms';
    case Database = 'database';
    case Push = 'push';
}
