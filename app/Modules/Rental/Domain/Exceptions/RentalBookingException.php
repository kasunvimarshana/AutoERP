<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\Exceptions;

class RentalBookingException extends \RuntimeException
{
    public static function assetNotAvailable(int $assetId, string $status): self
    {
        return new self("Asset #{$assetId} is not available for rental (current status: {$status}).");
    }

    public static function conflictingBookingExists(int $assetId): self
    {
        return new self("Asset #{$assetId} has a conflicting confirmed booking for the requested period.");
    }

    public static function invalidTransition(string $from, string $to): self
    {
        return new self("Cannot transition rental booking from '{$from}' to '{$to}'.");
    }

    public static function notFound(int $id): self
    {
        return new self("Rental booking #{$id} not found.");
    }
}
