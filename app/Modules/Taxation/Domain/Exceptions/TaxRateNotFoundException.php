<?php

declare(strict_types=1);

namespace Modules\Taxation\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class TaxRateNotFoundException extends NotFoundException
{
    public function __construct(mixed $id = null)
    {
        parent::__construct('TaxRate', $id);
    }
}
