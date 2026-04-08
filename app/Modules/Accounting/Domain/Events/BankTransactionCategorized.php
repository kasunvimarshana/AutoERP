<?php
declare(strict_types=1);
namespace Modules\Accounting\Domain\Events;
use Modules\Accounting\Domain\Entities\BankTransaction;
class BankTransactionCategorized {
    public function __construct(public readonly BankTransaction $transaction) {}
}
