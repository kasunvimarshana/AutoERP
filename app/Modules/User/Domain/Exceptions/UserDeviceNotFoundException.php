<?php

declare(strict_types=1);

namespace Modules\User\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class UserDeviceNotFoundException extends NotFoundException
{
    public function __construct(mixed $id = null)
    {
        parent::__construct('User device', $id);
    }
}
