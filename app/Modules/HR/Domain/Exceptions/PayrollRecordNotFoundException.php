<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class PayrollRecordNotFoundException extends NotFoundException
{
    public function __construct(mixed $id = null)
    {
        parent::__construct('PayrollRecord', $id);
    }
}
