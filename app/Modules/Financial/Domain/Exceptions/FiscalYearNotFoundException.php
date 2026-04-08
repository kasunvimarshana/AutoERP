<?php

declare(strict_types=1);

namespace Modules\Financial\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class FiscalYearNotFoundException extends NotFoundException
{
    public function __construct(string $id)
    {
        parent::__construct('FiscalYear', $id);
    }
}
