<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\TaxRules;

use Modules\Core\Application\Results\Result;

interface ListTaxRulesServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}
