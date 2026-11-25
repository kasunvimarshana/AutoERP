<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Services\Capture;

use Carbon\CarbonImmutable;
use Modules\Hr\Models\HrAttendanceKiosk;

final class DynamicQrSecurityService
{
    /**
     * Generate dynamic signed rotating QR payload for a kiosk.
     *
     * @return array{token: string, expires_in_seconds: int, kiosk_code: string}
     */
    public function generateToken(HrAttendanceKiosk $kiosk): array
    {
        $rotationSeconds = max(10, (int) $kiosk->rotation_seconds);
        $currentUnix = CarbonImmutable::now()->getTimestamp();
        $window = (int) floor($currentUnix / $rotationSeconds);

        $payload = "{$kiosk->id}:{$window}";
        $signature = hash_hmac('sha256', $payload, (string) $kiosk->secret_key);
        $token = base64_encode("{$payload}:{$signature}");

        $secondsRemaining = $rotationSeconds - ($currentUnix % $rotationSeconds);

        return [
            'token' => $token,
            'expires_in_seconds' => $secondsRemaining,
            'kiosk_code' => (string) $kiosk->kiosk_code,
        ];
    }

    /**
     * Verify whether a dynamic rotating QR token is valid and signed by this kiosk.
     */
    public function verifyToken(HrAttendanceKiosk $kiosk, string $token): bool
    {
        $decoded = base64_decode($token, true);
        if ($decoded === false) {
            return false;
        }

        $parts = explode(':', $decoded);
        if (count($parts) !== 3) {
            return false;
        }

        [$kioskIdStr, $windowStr, $providedSignature] = $parts;
        if ((int) $kioskIdStr !== (int) $kiosk->id) {
            return false;
        }

        $tokenWindow = (int) $windowStr;
        $rotationSeconds = max(10, (int) $kiosk->rotation_seconds);
        $currentUnix = CarbonImmutable::now()->getTimestamp();
        $currentWindow = (int) floor($currentUnix / $rotationSeconds);

        // Allow tolerance of 1 rotation window prior or next to accommodate minor latency/clock drift
        if (abs($currentWindow - $tokenWindow) > 1) {
            return false;
        }

        $payload = "{$kiosk->id}:{$tokenWindow}";
        $expectedSignature = hash_hmac('sha256', $payload, (string) $kiosk->secret_key);

        return hash_equals($expectedSignature, $providedSignature);
    }
}
