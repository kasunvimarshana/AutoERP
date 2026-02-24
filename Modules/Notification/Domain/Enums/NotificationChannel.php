<?php
namespace Modules\Notification\Domain\Enums;
enum NotificationChannel: string
{
    case InApp = 'in_app';
    case Email = 'email';
    case Sms = 'sms';
    case Push = 'push';
    case Webhook = 'webhook';
}
