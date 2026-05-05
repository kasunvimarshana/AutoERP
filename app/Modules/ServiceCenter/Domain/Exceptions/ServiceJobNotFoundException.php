<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Domain\Exceptions;

use RuntimeException;

class ServiceJobNotFoundException extends RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct(sprintf('Service job not found for id: %d', $id));
    }
}
