<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\TaxRules;

use Modules\Core\Application\Results\Result;

interface DeleteTaxRuleServiceInterface
{
    public function execute(int|string $id): Result;
}
