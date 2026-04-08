<?php

declare(strict_types=1);

namespace Modules\Taxation\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class TaxRuleNotFoundException extends NotFoundException
{
    public function __construct(mixed $id = null)
    {
        parent::__construct('TaxRule', $id);
    }
}
