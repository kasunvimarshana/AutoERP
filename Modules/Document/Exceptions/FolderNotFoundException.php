<?php

declare(strict_types=1);

namespace Modules\Document\Exceptions;

use Exception;

class FolderNotFoundException extends Exception
{
    public function __construct(string $message = 'Folder not found')
    {
        parent::__construct($message, 404);
    }
}
