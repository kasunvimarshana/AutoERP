<?php

declare(strict_types=1);

namespace Modules\Notification\Exceptions;

use Modules\Core\Exceptions\BaseException;

class NotificationTemplateNotFoundException extends BaseException
{
    protected $message = 'Notification template not found';

    protected $code = 404;
}
