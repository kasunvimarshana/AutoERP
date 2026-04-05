<?php
declare(strict_types=1);
namespace Modules\POS\Domain\Exceptions;

class PosTransactionNotFoundException extends \RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("POS transaction with ID {$id} not found.");
    }
}
