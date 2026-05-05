<?php

declare(strict_types=1);

namespace Modules\Service\Domain\Exceptions;

use RuntimeException;

class ServiceMaintenancePlanNotFoundException extends RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("Service maintenance plan #{$id} not found.");
    }
}
