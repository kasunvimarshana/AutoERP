<?php

namespace App\Domain\Order\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class OrderException extends DomainException
{
    public static function alreadyCancelled(string $id): self
    {
        return new self("Order [{$id}] is already cancelled.");
    }

    public static function cannotCancelShipped(string $id): self
    {
        return new self("Order [{$id}] has already been shipped and cannot be cancelled.");
    }

    public static function mustBePaidBeforeShipping(string $id): self
    {
        return new self("Order [{$id}] must be in Paid status before it can be shipped.");
    }

    public static function cannotMarkPaid(string $id, string $currentStatus): self
    {
        return new self("Order [{$id}] cannot be marked as paid — current status: [{$currentStatus}].");
    }

    public static function notFound(string $id): self
    {
        return new self("Order [{$id}] was not found.", 404);
    }
}
