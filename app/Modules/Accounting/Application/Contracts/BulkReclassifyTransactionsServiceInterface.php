<?php
declare(strict_types=1);
namespace Modules\Accounting\Application\Contracts;
interface BulkReclassifyTransactionsServiceInterface {
    public function reclassify(string $tenantId, array $transactionIds, string $newAccountId): int;
}
