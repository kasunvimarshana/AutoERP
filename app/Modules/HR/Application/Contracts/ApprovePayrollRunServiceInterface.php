<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts;

use Modules\Core\Application\Contracts\ServiceInterface;

/**
 * @method \Modules\HR\Domain\Entities\PayrollRun execute(array $data = [])
 */
interface ApprovePayrollRunServiceInterface extends ServiceInterface {}
