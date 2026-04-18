<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class FiscalYearNotFoundException extends NotFoundException
{
    public function __construct(int $id)
    {
        parent::__construct('Fiscal year', $id);
    }
}
