<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class PriceListNotFoundException extends NotFoundException
{
    public function __construct(int $id)
    {
        parent::__construct('PriceList', $id);
    }
}
