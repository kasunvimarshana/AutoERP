<?php

declare(strict_types=1);

namespace Modules\User\Application\Contracts;

use Modules\Core\Application\Contracts\WriteServiceInterface;

/**
 * @method void execute(array $data = [])
 */
interface SetUserPasswordServiceInterface extends WriteServiceInterface {}
