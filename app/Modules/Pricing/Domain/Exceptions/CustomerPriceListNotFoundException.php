<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class CustomerPriceListNotFoundException extends NotFoundException
{
    public function __construct(int $id)
    {
        parent::__construct('CustomerPriceList', $id);
    }
}
