<?php

namespace Modules\Tenant\Application\Contracts;

use Modules\Core\Application\Contracts\WriteServiceInterface;

/**
 * @method \Modules\Tenant\Domain\Entities\Tenant execute(array $data = [])
 */
interface UpdateTenantServiceInterface extends WriteServiceInterface
{
}
