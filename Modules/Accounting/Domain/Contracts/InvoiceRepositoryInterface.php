<?php

namespace Modules\Accounting\Domain\Contracts;

use Modules\Shared\Domain\Contracts\RepositoryInterface;

interface InvoiceRepositoryInterface extends RepositoryInterface
{
    public function nextNumber(string $tenantId): string;
    public function nextCreditNoteNumber(string $tenantId): string;
    public function paginate(array $filters, int $perPage = 15): object;
}
