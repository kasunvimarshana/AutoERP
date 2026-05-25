<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\PayslipLines;

use Modules\Core\Application\Results\Result;

interface CreatePayslipLineServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}