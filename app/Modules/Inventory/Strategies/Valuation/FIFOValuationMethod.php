<?php

declare(strict_types=1);

namespace Modules\Inventory\Strategies\Valuation;

use Modules\Inventory\Enums\ValuationMethod;

final class FIFOValuationMethod extends AbstractLayerValuationMethod
{
    protected function method(): ValuationMethod
    {
        return ValuationMethod::FIFO;
    }
}
