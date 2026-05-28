<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Constants;

final class PaymentStatus
{
    public const DRAFT = 'draft';
    public const POSTED = 'posted';
    public const RECONCILED = 'reconciled';
    public const REVERSED = 'reversed';
    public const VOIDED = 'voided';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::DRAFT,
            self::POSTED,
            self::RECONCILED,
            self::REVERSED,
            self::VOIDED,
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
            self::DRAFT => [self::POSTED, self::VOIDED],
            self::POSTED => [self::RECONCILED, self::VOIDED],
            self::RECONCILED => [self::POSTED, self::REVERSED],
            self::REVERSED => [],
            self::VOIDED => [],
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
