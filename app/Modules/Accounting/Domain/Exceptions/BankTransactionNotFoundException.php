<?php
declare(strict_types=1);
namespace Modules\Accounting\Domain\Exceptions;

class BankTransactionNotFoundException extends \RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("Bank transaction with ID {$id} not found.");
    }
}
