<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\DomainException;

class FiscalPeriodNotFoundException extends DomainException
{
    public function __construct(string $message = 'Fiscal period not found.')
    {
        parent::__construct($message, 404);
    }

    public static function byId(int $id): self
    {
        return new self(sprintf('Fiscal period with id %d not found.', $id));
    }
}
