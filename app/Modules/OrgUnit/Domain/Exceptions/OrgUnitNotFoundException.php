<?php
declare(strict_types=1);
namespace Modules\OrgUnit\Domain\Exceptions;

class OrgUnitNotFoundException extends \RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("OrgUnit #{$id} not found.");
    }
}
