<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class NumberingSequenceNotFoundException extends NotFoundException
{
    public function __construct(int|string $id)
    {
        parent::__construct('NumberingSequence', $id);
    }
}
