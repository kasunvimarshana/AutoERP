<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Inventory\Strategies\Valuation;

use Modules\Inventory\Enums\ValuationMethod;

final class FIFOValuationMethod extends AbstractLayerValuationMethod
{
    protected function method(): ValuationMethod
    {
        return ValuationMethod::FIFO;
    }
}
