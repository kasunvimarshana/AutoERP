<?php

declare(strict_types=1);

namespace Modules\Workflow\Enums;

enum ActionType: string
{
    case CREATE_RECORD = 'create_record';
    case UPDATE_RECORD = 'update_record';
    case DELETE_RECORD = 'delete_record';
    case SEND_NOTIFICATION = 'send_notification';
    case SEND_EMAIL = 'send_email';
    case WEBHOOK = 'webhook';
    case SCRIPT = 'script';
    case WAIT = 'wait';

    public function label(): string
    {
        return match ($this) {
            self::CREATE_RECORD => 'Create Record',
            self::UPDATE_RECORD => 'Update Record',
            self::DELETE_RECORD => 'Delete Record',
            self::SEND_NOTIFICATION => 'Send Notification',
            self::SEND_EMAIL => 'Send Email',
            self::WEBHOOK => 'Webhook',
            self::SCRIPT => 'Script',
            self::WAIT => 'Wait',
        };
    }

    public function requiresConfiguration(): bool
    {
        return true;
    }
}
