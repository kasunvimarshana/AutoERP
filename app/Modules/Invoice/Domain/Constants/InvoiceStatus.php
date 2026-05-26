<?php

declare(strict_types=1);

namespace Modules\Invoice\Domain\Constants;

final class InvoiceStatus
{
    public const DRAFT = 'draft';
    public const APPROVED = 'approved';
    public const PARTIALLY_PAID = 'partially_paid';
    public const PAID = 'paid';
    public const DISPUTED = 'disputed';
    public const CANCELLED = 'cancelled';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::DRAFT,
            self::APPROVED,
            self::PARTIALLY_PAID,
            self::PAID,
            self::DISPUTED,
            self::CANCELLED,
        ];
    }

    public static function isValid(string $status): bool
    {
        return in_array($status, self::values(), true);
    }

    /**
     * @return array<string, list<string>>
     */
    public static function transitions(): array
    {
        return [
            self::DRAFT => [self::APPROVED, self::CANCELLED],
            self::APPROVED => [self::PARTIALLY_PAID, self::PAID, self::DISPUTED, self::CANCELLED],
            self::PARTIALLY_PAID => [self::PAID, self::DISPUTED, self::CANCELLED],
            self::PAID => [self::DISPUTED],
            self::DISPUTED => [self::APPROVED, self::PARTIALLY_PAID, self::PAID, self::CANCELLED],
            self::CANCELLED => [],
        ];
    }

    public static function canTransition(string $from, string $to): bool
    {
        $allowed = self::transitions()[$from] ?? [];

        return in_array($to, $allowed, true);
    }

    private function __construct()
    {
    }
}
