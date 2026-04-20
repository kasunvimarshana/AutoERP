<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\DomainException;

class NoApplicablePriceFoundException extends DomainException
{
    public function __construct(string $message = 'No applicable price found for the provided context.')
    {
        parent::__construct($message);
    }
}
