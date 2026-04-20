<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts;

use Modules\Core\Application\Contracts\ServiceInterface;

/**
 * @method \Modules\Finance\Domain\Entities\ApTransaction execute(array $data = [])
 */
interface ReconcileApTransactionServiceInterface extends ServiceInterface {}
