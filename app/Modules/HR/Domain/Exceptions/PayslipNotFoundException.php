<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class PayslipNotFoundException extends NotFoundException
{
    public function __construct(int $id)
    {
        parent::__construct('Payslip', $id);
    }
}
