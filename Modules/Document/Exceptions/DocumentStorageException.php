<?php

declare(strict_types=1);

namespace Modules\Document\Exceptions;

use Exception;

class DocumentStorageException extends Exception
{
    public function __construct(string $message = 'Document storage operation failed')
    {
        parent::__construct($message, 500);
    }
}
