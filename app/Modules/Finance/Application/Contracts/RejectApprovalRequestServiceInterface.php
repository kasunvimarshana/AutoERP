<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts;

use Modules\Core\Application\Contracts\ServiceInterface;

/**
 * @method \Modules\Finance\Domain\Entities\ApprovalRequest execute(array $data = [])
 */
interface RejectApprovalRequestServiceInterface extends ServiceInterface {}
