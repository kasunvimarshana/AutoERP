<?php

declare(strict_types=1);

namespace Modules\Settings\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class SettingNotFoundException extends NotFoundException
{
    public function __construct(mixed $id = null)
    {
        parent::__construct('Setting', $id);
    }
}
