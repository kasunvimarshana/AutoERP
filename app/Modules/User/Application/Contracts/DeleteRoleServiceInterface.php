<?php

declare(strict_types=1);

namespace Modules\User\Application\Contracts;

use Modules\Core\Application\Contracts\WriteServiceInterface;

/**
 * @method bool execute(array $data = [])
 */
interface DeleteRoleServiceInterface extends WriteServiceInterface {}
