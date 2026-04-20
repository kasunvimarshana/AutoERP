<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class PriceListItemNotFoundException extends NotFoundException
{
    public function __construct(int $id)
    {
        parent::__construct('PriceListItem', $id);
    }
}
