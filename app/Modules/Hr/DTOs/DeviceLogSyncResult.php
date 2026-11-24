<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\DTOs;

final readonly class DeviceLogSyncResult
{
    /**
     * @param  list<RawBiometricPunchData>  $punches
     */
    public function __construct(
        public bool $isSuccess,
        public array $punches = [],
        public ?string $nextCursor = null,
        public int $fetchedCount = 0,
        public ?string $errorMessage = null,
    ) {}

    /**
     * @param  list<RawBiometricPunchData>  $punches
     */
    public static function success(array $punches, ?string $nextCursor = null): self
    {
        return new self(
            isSuccess: true,
            punches: $punches,
            nextCursor: $nextCursor,
            fetchedCount: count($punches),
        );
    }

    public static function failure(string $errorMessage): self
    {
        return new self(
            isSuccess: false,
            errorMessage: $errorMessage,
        );
    }
}
