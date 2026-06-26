<?php

declare(strict_types=1);

namespace Modules\Auth\DTOs;

use Illuminate\Http\Request;

final readonly class ClientContext
{
    public function __construct(
        public string $ipAddress,
        public ?string $userAgent,
        public ?string $deviceName,
    ) {}

    public static function fromRequest(Request $request, ?string $deviceName = null): self
    {
        $ipAddress = trim((string) $request->ip());
        if ($ipAddress === '') {
            $ipAddress = '0.0.0.0';
        }

        $userAgent = self::nullableBoundedString($request->userAgent(), 1024);
        $deviceName = self::nullableBoundedString($deviceName, 160);

        return new self($ipAddress, $userAgent, $deviceName);
    }

    private static function nullableBoundedString(?string $value, int $maximumLength): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, $maximumLength);
    }
}
