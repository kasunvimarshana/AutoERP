<?php

declare(strict_types=1);

namespace Modules\Notification\Enums;

enum NotificationType: string
{
    case EMAIL = 'email';
    case SMS = 'sms';
    case PUSH = 'push';
    case IN_APP = 'in_app';
    case SLACK = 'slack';
    case WEBHOOK = 'webhook';

    public function label(): string
    {
        return match ($this) {
            self::EMAIL => 'Email',
            self::SMS => 'SMS',
            self::PUSH => 'Push Notification',
            self::IN_APP => 'In-App Notification',
            self::SLACK => 'Slack',
            self::WEBHOOK => 'Webhook',
        };
    }
}
