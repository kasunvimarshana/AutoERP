<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Contracts;

use Modules\Core\Application\Contracts\ServiceInterface;

/**
 * @method \Modules\Tenant\Domain\Entities\TenantPlan execute(array $data = [])
 */
interface CreateTenantPlanServiceInterface extends ServiceInterface {}
