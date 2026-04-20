<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts;

use Modules\Core\Application\Contracts\ServiceInterface;

/**
 * @method \Modules\Finance\Domain\Entities\BankTransaction execute(array $data = [])
 */
interface CategorizeBankTransactionServiceInterface extends ServiceInterface {}
