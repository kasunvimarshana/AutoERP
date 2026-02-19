<?php

declare(strict_types=1);

namespace Modules\Notification\Exceptions;

use Modules\Core\Exceptions\BaseException;

class InvalidTemplateDataException extends BaseException
{
    protected $message = 'Invalid template data provided';

    protected $code = 422;
}
