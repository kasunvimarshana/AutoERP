<?php

declare(strict_types=1);

namespace Modules\Transaction\Application\Contracts;

use Modules\Transaction\Domain\Entities\TransactionLine;

interface TransactionLineServiceInterface
{
    public function getLine(string $tenantId, string $id): TransactionLine;

    /** @return TransactionLine[] */
    public function getLinesForTransaction(string $tenantId, string $transactionId): array;

    public function addLine(string $tenantId, array $data): TransactionLine;

    public function updateLine(string $tenantId, string $id, array $data): TransactionLine;

    public function deleteLine(string $tenantId, string $id): void;
}
