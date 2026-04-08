<?php

declare(strict_types=1);

namespace Modules\GS1\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class Gs1BarcodeNotFoundException extends NotFoundException
{
    public function __construct(mixed $id = null)
    {
        parent::__construct('Gs1Barcode', $id);
    }
}
