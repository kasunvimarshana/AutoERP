<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Constants;

final class JournalEntryStatus
{
    public const DRAFT = 'DRAFT';
    public const POSTED = 'POSTED';
    public const REVERSED = 'REVERSED';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::DRAFT,
            self::POSTED,
            self::REVERSED,
        ];
    }

    public static function isValid(string $status): bool
    {
        return in_array(strtoupper(trim($status)), self::values(), true);
    }

    /**
     * @return array<string, list<string>>
     */
    public static function transitions(): array
    {
        return [
            self::DRAFT => [self::POSTED],
            self::POSTED => [self::REVERSED],
            self::REVERSED => [],
        ];
    }

    public static function canTransition(string $from, string $to): bool
    {
        $fromKey = strtoupper(trim($from));
        $toValue = strtoupper(trim($to));
        $allowed = self::transitions()[$fromKey] ?? [];

        return in_array($toValue, $allowed, true);
    }

    private function __construct()
    {
    }
}
