<?php
declare(strict_types=1);
namespace Modules\Authorization\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class PermissionNotFoundException extends NotFoundException
{
    public function __construct(int|string $id)
    {
        parent::__construct('Permission', $id);
    }
}
