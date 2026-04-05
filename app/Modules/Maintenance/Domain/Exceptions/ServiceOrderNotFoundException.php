<?php
declare(strict_types=1);
namespace Modules\Maintenance\Domain\Exceptions;
class ServiceOrderNotFoundException extends \RuntimeException {
    public function __construct(int $id) { parent::__construct("Service order {$id} not found."); }
}
