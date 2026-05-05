<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\DomainException;

class UomConversionPathNotFoundException extends DomainException
{
    public function __construct(int $fromUomId, int $toUomId)
    {
        parent::__construct(
            sprintf('No UOM conversion path exists from UOM %d to UOM %d.', $fromUomId, $toUomId),
            404
        );
    }
}
