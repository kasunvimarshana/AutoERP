<?php
declare(strict_types=1);
namespace Modules\Accounting\Domain\Events;

class TransactionCategorized
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $transactionId,
        public readonly int $categoryId,
    ) {}
}
