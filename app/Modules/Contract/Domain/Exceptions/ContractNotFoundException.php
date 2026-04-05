<?php
declare(strict_types=1);
namespace Modules\Contract\Domain\Exceptions;
class ContractNotFoundException extends \RuntimeException {
    public function __construct(int $id) { parent::__construct("Contract {$id} not found."); }
}
