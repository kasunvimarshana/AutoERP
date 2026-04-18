<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts;

use Modules\Core\Application\Contracts\ServiceInterface;

/**
 * @method \Modules\Finance\Domain\Entities\FiscalPeriod execute(array $data = [])
 */
interface CreateFiscalPeriodServiceInterface extends ServiceInterface {}
