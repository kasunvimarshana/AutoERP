<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts;

use Modules\Core\Application\Contracts\ServiceInterface;

/**
 * @method \Modules\HR\Domain\Entities\LeaveRequest execute(array $data = [])
 */
interface CancelLeaveRequestServiceInterface extends ServiceInterface {}
