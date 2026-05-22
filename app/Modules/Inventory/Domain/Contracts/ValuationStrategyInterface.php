<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Contracts;

use Modules\Inventory\Application\DTOs\ValuationRequest;
use Modules\Inventory\Application\DTOs\ValuationResult;

interface ValuationStrategyInterface
{
    public function calculate(ValuationRequest $request): ValuationResult;
}
