<?php

declare(strict_types=1);

namespace Modules\Service\Domain\Exceptions;

use RuntimeException;

class ServiceJobCardCannotBeCompletedException extends RuntimeException
{
    public function __construct(int $id, string $reason = '')
    {
        $message = "Service job card #{$id} cannot be completed.";
        if ($reason !== '') {
            $message .= " {$reason}";
        }
        parent::__construct($message);
    }
}
