<?php

declare(strict_types=1);

namespace Modules\Document\Exceptions;

use Exception;

class DocumentNotFoundException extends Exception
{
    public function __construct(string $message = 'Document not found')
    {
        parent::__construct($message, 404);
    }
}
