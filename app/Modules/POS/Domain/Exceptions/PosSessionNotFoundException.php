<?php
declare(strict_types=1);
namespace Modules\POS\Domain\Exceptions;

class PosSessionNotFoundException extends \RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("POS session with ID {$id} not found.");
    }
}
