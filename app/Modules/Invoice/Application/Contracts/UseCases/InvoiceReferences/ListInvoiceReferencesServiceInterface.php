<?php

declare(strict_types=1);

namespace Modules\Invoice\Application\Contracts\UseCases\InvoiceReferences;

use Modules\Core\Application\Results\Result;

interface ListInvoiceReferencesServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}