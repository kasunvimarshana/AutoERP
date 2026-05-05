<?php

declare(strict_types=1);

namespace Modules\Fleet\Domain\ValueObjects;

final class VehicleState
{
    public const AVAILABLE   = 'available';
    public const RENTED      = 'rented';
    public const IN_SERVICE  = 'in_service';
    public const MAINTENANCE = 'maintenance';
    public const RETIRED     = 'retired';

    /** @var array<string, list<string>> Valid transitions: from => [to, ...] */
    private const TRANSITIONS = [
        self::AVAILABLE   => [self::RENTED, self::IN_SERVICE, self::MAINTENANCE, self::RETIRED],
        self::RENTED      => [self::AVAILABLE, self::MAINTENANCE],
        self::IN_SERVICE  => [self::AVAILABLE, self::MAINTENANCE],
        self::MAINTENANCE => [self::AVAILABLE, self::RETIRED],
        self::RETIRED     => [],
    ];

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public static function all(): array
    {
        return [self::AVAILABLE, self::RENTED, self::IN_SERVICE, self::MAINTENANCE, self::RETIRED];
    }
}
