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
use Illuminate\Http\Request;
use Illuminate\Http\Response as LaravelResponse;
use Modules\Hr\Biometric\Contracts\BiometricDeviceAdapterInterface;
use Modules\Hr\DTOs\DeviceHealthResult;
use Modules\Hr\DTOs\DeviceLogSyncResult;
use Modules\Hr\DTOs\RawBiometricPunchData;
use Modules\Hr\Enums\BiometricPunchState;
use Modules\Hr\Enums\BiometricVerifyMethod;
use Modules\Hr\Models\HrBiometricDevice;
use Symfony\Component\HttpFoundation\Response;

final class ZkTecoPushAdapter implements BiometricDeviceAdapterInterface
{
    public function ping(HrBiometricDevice $device): DeviceHealthResult
    {
        $lastSync = $device->last_sync_at ?? $device->last_ping_at;

        if ($lastSync === null) {
            return DeviceHealthResult::failure('Device has not connected to ADMS push service yet.');
        }

        $secondsAgo = (int) CarbonImmutable::now()->diffInSeconds($lastSync);
        $isOnline = $secondsAgo <= 180; // Typically heartbeats every 10-60s

        if (! $isOnline) {
            return DeviceHealthResult::failure("Device is offline. Last heard {$secondsAgo} seconds ago.");
        }

        return DeviceHealthResult::success(
            responseTimeMs: 25,
            firmwareVersion: $device->settings['push_version'] ?? 'PushSDK-ADMS',
            deviceTime: $lastSync->toIso8601String(),
            clockDriftSeconds: 0,
            metadata: [
                'connection_type' => 'HTTP_PUSH_ADMS',
                'seconds_since_last_seen' => $secondsAgo,
            ],
        );
    }

    public function syncTime(HrBiometricDevice $device, CarbonImmutable $targetTime): bool
    {
        // Enqueue command into device settings to be picked up on next /iclock/getrequest
        $settings = $device->settings ?? [];
        $settings['pending_commands'][] = [
            'id' => uniqid('cmd_time_'),
            'command' => 'SET TIME '.$targetTime->format('Y-m-d H:i:s'),
            'queued_at' => CarbonImmutable::now()->toIso8601String(),
        ];
        $device->update(['settings' => $settings]);

        return true;
    }

    public function fetchLogs(HrBiometricDevice $device, ?string $cursor = null): DeviceLogSyncResult
    {
        // In Push protocol, the device pushes logs directly to the server.
        return DeviceLogSyncResult::success([], $cursor);
    }

    public function clearLogs(HrBiometricDevice $device): bool
    {
        $settings = $device->settings ?? [];
        $settings['pending_commands'][] = [
            'id' => uniqid('cmd_clear_'),
            'command' => 'CLEAR LOG',
            'queued_at' => CarbonImmutable::now()->toIso8601String(),
        ];
        $device->update(['settings' => $settings]);

        return true;
    }

    public function parseIncomingPush(HrBiometricDevice $device, Request $request): DeviceLogSyncResult
    {
        $content = (string) $request->getContent();
        if (trim($content) === '') {
            return DeviceLogSyncResult::success([]);
        }

        $lines = preg_split("/\r\n|\n|\r/", trim($content));
        if ($lines === false || count($lines) === 0) {
            return DeviceLogSyncResult::success([]);
        }

        $punches = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            // Detect delimiter: tab or comma
            $parts = str_contains($line, "\t")
                ? explode("\t", $line)
                : explode(',', $line);

            if (count($parts) < 2) {
                continue;
            }

            $pin = trim($parts[0]);
            $timeStr = trim($parts[1]);

            // Validate date format
            try {
                $punchTime = CarbonImmutable::parse($timeStr)->toDateTimeString();
            } catch (\Throwable) {
                continue;
            }

            $stateCode = isset($parts[2]) ? (int) trim($parts[2]) : 0;
            $verifyCode = isset($parts[3]) ? (int) trim($parts[3]) : 1;
            $workCode = isset($parts[4]) ? trim($parts[4]) : null;

            $punchState = match ($stateCode) {
                0 => BiometricPunchState::CheckIn,
                1 => BiometricPunchState::CheckOut,
                2 => BiometricPunchState::BreakOut,
                3 => BiometricPunchState::BreakIn,
                4 => BiometricPunchState::OvertimeIn,
                5 => BiometricPunchState::OvertimeOut,
                default => BiometricPunchState::Auto,
            };

            $verifyMethod = match ($verifyCode) {
                1 => BiometricVerifyMethod::Fingerprint,
                2 => BiometricVerifyMethod::Password,
                4 => BiometricVerifyMethod::Card,
                15 => BiometricVerifyMethod::Face,
                default => BiometricVerifyMethod::Unknown,
            };

            $punches[] = new RawBiometricPunchData(
                biometricUserId: $pin,
                punchTime: $punchTime,
                punchState: $punchState,
                verifyMethod: $verifyMethod,
                workCode: $workCode,
                rawPayload: $line,
            );
        }

        return DeviceLogSyncResult::success($punches);
    }

    public function generatePushResponse(HrBiometricDevice $device, Request $request, DeviceLogSyncResult $syncResult): Response
    {
        $count = $syncResult->fetchedCount;

        return new LaravelResponse(
            content: "OK: {$count}\n",
            status: 200,
            headers: ['Content-Type' => 'text/plain'],
        );
    }

    /**
     * Generate response for ZKTeco handshake /iclock/cdata GET request.
     */
    public function generateHandshakeResponse(HrBiometricDevice $device): Response
    {
        $sn = $device->serial_number ?? 'UNKNOWN';
        $tzOffsetMinutes = 330; // Default +05:30

        $body = "GET OPTION FROM: {$sn}\n"
            ."Stamp=9999\n"
            ."OpStamp=0\n"
            ."ErrorDelay=30\n"
            ."Delay=10\n"
            ."TransTimes=00:00;14:05\n"
            ."TransInterval=1\n"
            ."TransFlag=1111111111\n"
            ."TimeZone={$tzOffsetMinutes}\n"
            ."Realtime=1\n"
            ."Encrypt=0\n";

        return new LaravelResponse(
            content: $body,
            status: 200,
            headers: ['Content-Type' => 'text/plain'],
        );
    }

    /**
     * Generate response for ZKTeco /iclock/getrequest polling.
     */
    public function generateCommandResponse(HrBiometricDevice $device): Response
    {
        $settings = $device->settings ?? [];
        $commands = $settings['pending_commands'] ?? [];

        if (count($commands) === 0) {
            return new LaravelResponse("OK\n", 200, ['Content-Type' => 'text/plain']);
        }

        $nextCmd = array_shift($commands);
        $settings['pending_commands'] = $commands;
        $device->update(['settings' => $settings]);

        $body = "C:{$nextCmd['id']}:{$nextCmd['command']}\n";

        return new LaravelResponse($body, 200, ['Content-Type' => 'text/plain']);
    }
}
