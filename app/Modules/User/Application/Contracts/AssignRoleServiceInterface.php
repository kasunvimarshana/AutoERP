<?php

declare(strict_types=1);

namespace Modules\User\Application\Contracts;

use Modules\Core\Application\Contracts\WriteServiceInterface;

/**
 * @method null execute(array $data = [])
 */
interface AssignRoleServiceInterface extends WriteServiceInterface {}
