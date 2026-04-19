<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class FiscalPeriodNotFoundException extends NotFoundException
{
    public function __construct(int $id)
    {
        parent::__construct('Fiscal period', $id);
    }

    public static function byId(int $id): self
    {
        return new self($id);
    }

    public static function openPeriodForId(int $id): self
    {
        return new self($id);
    }
}
