<?php
declare(strict_types=1);
namespace Modules\POS\Domain\Exceptions;

class PosTerminalNotFoundException extends \RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("POS terminal with ID {$id} not found.");
    }
}
