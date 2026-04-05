<?php
declare(strict_types=1);
namespace Modules\Accounting\Domain\Exceptions;

class BankAccountNotFoundException extends \RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("Bank account with ID {$id} not found.");
    }
}
