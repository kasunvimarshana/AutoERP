<?php

namespace Modules\Integration\Domain\Enums;

enum WebhookStatus: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
}
