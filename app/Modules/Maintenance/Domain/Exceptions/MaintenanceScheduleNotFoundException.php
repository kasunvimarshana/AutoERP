<?php
declare(strict_types=1);
namespace Modules\Maintenance\Domain\Exceptions;
class MaintenanceScheduleNotFoundException extends \RuntimeException {
    public function __construct(int $id) { parent::__construct("Maintenance schedule {$id} not found."); }
}
