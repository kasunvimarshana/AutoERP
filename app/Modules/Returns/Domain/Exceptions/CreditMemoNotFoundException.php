<?php

declare(strict_types=1);

namespace Modules\Returns\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class CreditMemoNotFoundException extends NotFoundException
{
    public function __construct(mixed $id = null)
    {
        parent::__construct('CreditMemo', $id);
    }
}
