<?php

declare(strict_types=1);

namespace Modules\Service\Domain\Exceptions;

use RuntimeException;

class ServiceJobCardNotFoundException extends RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("Service job card #{$id} not found.");
    }
}
