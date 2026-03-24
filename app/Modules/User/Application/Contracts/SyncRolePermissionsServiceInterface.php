<?php

declare(strict_types=1);

namespace Modules\User\Application\Contracts;

use Modules\Core\Application\Contracts\WriteServiceInterface;

/**
 * @method \Modules\User\Domain\Entities\Role execute(array $data = [])
 */
interface SyncRolePermissionsServiceInterface extends WriteServiceInterface {}
