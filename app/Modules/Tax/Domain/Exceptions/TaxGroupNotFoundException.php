<?php
declare(strict_types=1);
namespace Modules\Tax\Domain\Exceptions;

class TaxGroupNotFoundException extends \RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("Tax group with ID {$id} not found.");
    }
}
