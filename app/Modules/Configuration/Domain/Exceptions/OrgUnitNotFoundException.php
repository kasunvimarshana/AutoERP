<?php
declare(strict_types=1);
namespace Modules\Configuration\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class OrgUnitNotFoundException extends NotFoundException
{
    public function __construct(int|string $id)
    {
        parent::__construct('OrgUnit', $id);
    }
}
