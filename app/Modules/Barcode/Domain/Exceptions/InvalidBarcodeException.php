<?php

declare(strict_types=1);

namespace Modules\Barcode\Domain\Exceptions;

use InvalidArgumentException;

class InvalidBarcodeException extends InvalidArgumentException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 422);
    }

    public static function forValue(string $value, string $type, string $reason): self
    {
        return new self("Barcode value [{$value}] is invalid for type [{$type}]: {$reason}");
    }
}
