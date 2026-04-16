<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class TenantPlanNotFoundException extends NotFoundException
{
    public function __construct(mixed $id = null)
    {
        parent::__construct('TenantPlan', $id);
    }
}
