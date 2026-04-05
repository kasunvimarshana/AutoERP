<?php
declare(strict_types=1);
namespace Modules\User\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class UserNotFoundException extends NotFoundException
{
    public function __construct(int|string $id)
    {
        parent::__construct('User', $id);
    }
}
