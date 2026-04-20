<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class BankCategoryRuleNotFoundException extends NotFoundException
{
    public function __construct(int|string $id)
    {
        parent::__construct('BankCategoryRule', $id);
    }
}
