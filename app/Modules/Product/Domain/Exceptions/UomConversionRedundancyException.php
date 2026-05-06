<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\DomainException;

class UomConversionRedundancyException extends DomainException
{
    public function __construct(int $fromUomId, int $toUomId)
    {
        parent::__construct(
            sprintf(
                'A conversion between UOM %d and UOM %d already exists in this scope. Store one direction only.',
                $fromUomId,
                $toUomId,
            ),
            409
        );
    }
}
