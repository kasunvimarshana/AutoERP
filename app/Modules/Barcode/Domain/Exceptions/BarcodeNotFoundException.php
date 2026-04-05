<?php

declare(strict_types=1);

namespace Modules\Barcode\Domain\Exceptions;

use RuntimeException;

class BarcodeNotFoundException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message, 404);
    }

    public static function withId(int $id): self
    {
        return new self("Barcode definition [{$id}] not found.");
    }

    public static function withValue(string $value): self
    {
        return new self("Barcode definition with value [{$value}] not found.");
    }
}
