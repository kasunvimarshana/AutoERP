<?php

declare(strict_types=1);

namespace Modules\Transaction\Domain\Events;

use Modules\Transaction\Domain\Entities\Transaction;

class TransactionPosted
{
    public function __construct(public readonly Transaction $transaction) {}
}
