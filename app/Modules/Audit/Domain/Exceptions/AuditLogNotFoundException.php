<?php
declare(strict_types=1);
namespace Modules\Audit\Domain\Exceptions;

class AuditLogNotFoundException extends \RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("Audit log entry with ID {$id} not found.");
    }
}
