<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Contracts;

use Modules\Core\Application\Contracts\WriteServiceInterface;

/**
 * @method \Modules\Tenant\Domain\Entities\TenantPlan execute(array $data = [])
 */
interface UpdateTenantPlanServiceInterface extends WriteServiceInterface {}
