<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Biometric\Adapters;

use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Hr\Biometric\Contracts\BiometricDeviceAdapterInterface;
use Modules\Hr\DTOs\DeviceHealthResult;
use Modules\Hr\DTOs\DeviceLogSyncResult;
use Modules\Hr\DTOs\RawBiometricPunchData;
use Modules\Hr\Enums\BiometricPunchState;
use Modules\Hr\Enums\BiometricVerifyMethod;
use Modules\Hr\Models\HrBiometricDevice;
use Symfony\Component\HttpFoundation\Response;

final class GenericWebhookAdapter implements BiometricDeviceAdapterInterface
{
    public function ping(HrBiometricDevice $device): DeviceHealthResult
    {
        $lastSync = $device->last_sync_at ?? $device->last_ping_at;

        if ($lastSync === null) {
            return DeviceHealthResult::failure('No webhook events received yet.');
        }

        $secondsAgo = (int) CarbonImmutable::now()->diffInSeconds($lastSync);

        return DeviceHealthResult::success(
            responseTimeMs: 5,
            firmwareVersion: 'Generic-Webhook-v1',
            deviceTime: $lastSync->toIso8601String(),
            metadata: ['seconds_since_last_event' => $secondsAgo],
        );
    }

    public function syncTime(HrBiometricDevice $device, CarbonImmutable $targetTime): bool
    {
        return true;
    }

    public function fetchLogs(HrBiometricDevice $device, ?string $cursor = null): DeviceLogSyncResult
    {
        return DeviceLogSyncResult::success([]);
    }

    public function clearLogs(HrBiometricDevice $device): bool
    {
        return true;
    }

    public function parseIncomingPush(HrBiometricDevice $device, Request $request): DeviceLogSyncResult
    {
        // Check HMAC or Auth Token if configured
        if (! empty($device->auth_token)) {
            $bearer = $request->bearerToken();
            $signature = $request->header('X-Biometric-Signature');

            if ($bearer !== $device->auth_token && ! $this->validateHmac($device->auth_token, (string) $request->getContent(), $signature)) {
                return DeviceLogSyncResult::failure('Unauthorized: Invalid biometric auth token or HMAC signature.');
            }
        }

        $data = $request->json()->all();
        $punchesRaw = isset($data['punches']) && is_array($data['punches'])
            ? $data['punches']
            : (isset($data['events']) && is_array($data['events']) ? $data['events'] : [$data]);

        $punches = [];

        foreach ($punchesRaw as $item) {
            if (! is_array($item)) {
                continue;
            }

            $pin = (string) ($item['user_id'] ?? $item['biometric_user_id'] ?? $item['pin'] ?? $item['employee_no'] ?? '');
            $timeStr = (string) ($item['punch_time'] ?? $item['timestamp'] ?? $item['time'] ?? '');

            if ($pin === '' || $timeStr === '') {
                continue;
            }

            try {
                $punchTime = CarbonImmutable::parse($timeStr)->toDateTimeString();
            } catch (\Throwable) {
                continue;
            }

            $stateStr = (string) ($item['state'] ?? $item['direction'] ?? $item['type'] ?? 'auto');
            $punchState = match (strtolower($stateStr)) {
                'in', 'check_in', 'checkin' => BiometricPunchState::CheckIn,
                'out', 'check_out', 'checkout' => BiometricPunchState::CheckOut,
                'break_out', 'breakout' => BiometricPunchState::BreakOut,
                'break_in', 'breakin' => BiometricPunchState::BreakIn,
                'ot_in', 'overtime_in' => BiometricPunchState::OvertimeIn,
                'ot_out', 'overtime_out' => BiometricPunchState::OvertimeOut,
                default => BiometricPunchState::Auto,
            };

            $verifyStr = (string) ($item['verify_method'] ?? $item['method'] ?? 'unknown');
            $verifyMethod = match (strtolower($verifyStr)) {
                'fingerprint', 'fp' => BiometricVerifyMethod::Fingerprint,
                'face' => BiometricVerifyMethod::Face,
                'card', 'rfid', 'nfc' => BiometricVerifyMethod::Card,
                'password', 'pin' => BiometricVerifyMethod::Password,
                'iris' => BiometricVerifyMethod::Iris,
                default => BiometricVerifyMethod::Unknown,
            };

            $punches[] = new RawBiometricPunchData(
                biometricUserId: $pin,
                punchTime: $punchTime,
                punchState: $punchState,
                verifyMethod: $verifyMethod,
                cardNumber: isset($item['card_number']) ? (string) $item['card_number'] : null,
                workCode: isset($item['work_code']) ? (string) $item['work_code'] : null,
                rawPayload: json_encode($item),
            );
        }

        return DeviceLogSyncResult::success($punches);
    }

    public function generatePushResponse(HrBiometricDevice $device, Request $request, DeviceLogSyncResult $syncResult): Response
    {
        return new JsonResponse([
            'success' => $syncResult->isSuccess,
            'received' => $syncResult->fetchedCount,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ]);
    }

    private function validateHmac(string $secret, string $payload, ?string $signature): bool
    {
        if (empty($signature)) {
            return false;
        }

        $computed = hash_hmac('sha256', $payload, $secret);

        return hash_equals($computed, $signature);
    }
}
