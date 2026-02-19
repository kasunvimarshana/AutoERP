<?php

declare(strict_types=1);

namespace Modules\Notification\Exceptions;

use Modules\Core\Exceptions\BaseException;

class NotificationChannelNotFoundException extends BaseException
{
    protected $message = 'Notification channel not found';

    protected $code = 404;
}
