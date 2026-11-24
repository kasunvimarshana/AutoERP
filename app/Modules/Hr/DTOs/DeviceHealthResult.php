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

final readonly class DeviceHealthResult
{
    public function __construct(
        public bool $isReachable,
        public ?int $responseTimeMs = null,
        public ?string $firmwareVersion = null,
        public ?string $deviceTime = null,
        public ?int $clockDriftSeconds = null,
        public ?string $errorMessage = null,
        /** @var array<string, mixed> */
        public array $metadata = [],
    ) {}

    public static function success(
        int $responseTimeMs,
        ?string $firmwareVersion = null,
        ?string $deviceTime = null,
        ?int $clockDriftSeconds = null,
        array $metadata = [],
    ): self {
        return new self(
            isReachable: true,
            responseTimeMs: $responseTimeMs,
            firmwareVersion: $firmwareVersion,
            deviceTime: $deviceTime,
            clockDriftSeconds: $clockDriftSeconds,
            metadata: $metadata,
        );
    }

    public static function failure(string $errorMessage): self
    {
        return new self(
            isReachable: false,
            errorMessage: $errorMessage,
        );
    }
}
