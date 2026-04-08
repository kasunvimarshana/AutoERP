<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Exceptions;

use App\Shared\Domain\ValueObjects\Uuid;

final class InvalidProductException extends \DomainException
{
    public static function priceCannotBeZero(Uuid $productId): self
    {
        return new self("Product [{$productId->value()}] price cannot be zero.");
    }

    public static function notFound(Uuid $productId): self
    {
        return new self("Product [{$productId->value()}] not found.");
    }
}
