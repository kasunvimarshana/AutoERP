<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\TaxRules;

use Modules\Core\Application\Results\Result;

interface CreateTaxRuleServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}
