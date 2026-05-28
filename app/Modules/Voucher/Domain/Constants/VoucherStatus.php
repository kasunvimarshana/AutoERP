<?php

declare(strict_types=1);

namespace Modules\Voucher\Domain\Constants;

final class VoucherStatus
{
    public const DRAFT = 'draft';
    public const SUBMITTED = 'submitted';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';
    public const POSTED = 'posted';
    public const CANCELLED = 'cancelled';
    public const REVERSED = 'reversed';

    public static function canTransition(string $from, string $to): bool
    {
        $map = [
            self::DRAFT => [self::SUBMITTED, self::CANCELLED],
            self::SUBMITTED => [self::APPROVED, self::REJECTED, self::CANCELLED],
            self::APPROVED => [self::POSTED, self::CANCELLED],
            self::REJECTED => [self::SUBMITTED, self::CANCELLED],
            self::POSTED => [self::REVERSED],
            self::CANCELLED => [],
            self::REVERSED => [],
        ];
        return in_array($to, $map[$from] ?? [], true);
    }

    private function __construct() {}
}