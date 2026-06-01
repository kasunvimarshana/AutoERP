<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Contracts\UseCases\SalesReturnLines;

use Modules\Core\Application\Results\Result;

interface CreateSalesReturnLineServiceInterface
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(array $payload): Result;
}
