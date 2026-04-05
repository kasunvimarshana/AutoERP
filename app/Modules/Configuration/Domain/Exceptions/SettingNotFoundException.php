<?php
declare(strict_types=1);
namespace Modules\Configuration\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class SettingNotFoundException extends NotFoundException
{
    public function __construct(int|string $key)
    {
        parent::__construct('Setting', $key);
    }
}
