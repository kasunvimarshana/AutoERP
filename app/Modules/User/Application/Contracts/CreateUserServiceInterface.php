<?php

declare(strict_types=1);

namespace Modules\User\Application\Contracts;

use Modules\Core\Application\Contracts\ServiceInterface;

/**
 * @method \Modules\User\Domain\Entities\User execute(array $data = [])
 */
interface CreateUserServiceInterface extends ServiceInterface {}
