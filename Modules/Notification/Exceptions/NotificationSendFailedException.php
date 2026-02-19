<?php

declare(strict_types=1);

namespace Modules\Notification\Exceptions;

use Modules\Core\Exceptions\BaseException;

class NotificationSendFailedException extends BaseException
{
    protected $message = 'Failed to send notification';

    protected $code = 500;
}
