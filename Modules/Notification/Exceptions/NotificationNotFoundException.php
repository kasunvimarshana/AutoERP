<?php

declare(strict_types=1);

namespace Modules\Notification\Exceptions;

use Modules\Core\Exceptions\BaseException;

class NotificationNotFoundException extends BaseException
{
    protected $message = 'Notification not found';

    protected $code = 404;
}
