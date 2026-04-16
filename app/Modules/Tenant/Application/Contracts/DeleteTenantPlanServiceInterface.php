<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Contracts;

use Modules\Core\Application\Contracts\WriteServiceInterface;

/**
 * @method bool execute(array $data = [])
 */
interface DeleteTenantPlanServiceInterface extends WriteServiceInterface {}
