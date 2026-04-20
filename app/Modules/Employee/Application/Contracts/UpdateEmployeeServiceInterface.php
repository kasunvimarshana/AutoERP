<?php

declare(strict_types=1);

namespace Modules\Employee\Application\Contracts;

use Modules\Core\Application\Contracts\ServiceInterface;

/**
 * @method \Modules\Employee\Domain\Entities\Employee execute(array $data = [])
 */
interface UpdateEmployeeServiceInterface extends ServiceInterface {}
