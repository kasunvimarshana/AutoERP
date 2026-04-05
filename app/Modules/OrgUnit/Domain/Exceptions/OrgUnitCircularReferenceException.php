<?php
declare(strict_types=1);
namespace Modules\OrgUnit\Domain\Exceptions;

class OrgUnitCircularReferenceException extends \RuntimeException
{
    public function __construct(int $unitId, int $newParentId)
    {
        parent::__construct(
            "Cannot move OrgUnit #{$unitId} under #{$newParentId}: circular reference detected."
        );
    }
}
