<?php
declare(strict_types=1);
namespace Modules\HR\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class AttendanceRecordNotFoundException extends NotFoundException
{
    public function __construct(int|string $id)
    {
        parent::__construct('AttendanceRecord', $id);
    }
}
