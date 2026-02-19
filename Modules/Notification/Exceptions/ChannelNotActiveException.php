<?php

declare(strict_types=1);

namespace Modules\Notification\Exceptions;

use Modules\Core\Exceptions\BaseException;

class ChannelNotActiveException extends BaseException
{
    protected $message = 'Notification channel is not active';

    protected $code = 422;
}
