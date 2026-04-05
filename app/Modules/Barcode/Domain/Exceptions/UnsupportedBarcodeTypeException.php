<?php

declare(strict_types=1);

namespace Modules\Barcode\Domain\Exceptions;

use RuntimeException;

class UnsupportedBarcodeTypeException extends RuntimeException
{
    public function __construct(string $type)
    {
        parent::__construct("Barcode type [{$type}] is not supported.", 422);
    }

    public static function forType(string $type): self
    {
        return new self($type);
    }
}
