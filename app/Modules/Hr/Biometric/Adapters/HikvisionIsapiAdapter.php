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
use Illuminate\Support\Facades\Http;
use Modules\Hr\Biometric\Contracts\BiometricDeviceAdapterInterface;
use Modules\Hr\DTOs\DeviceHealthResult;
use Modules\Hr\DTOs\DeviceLogSyncResult;
use Modules\Hr\DTOs\RawBiometricPunchData;
use Modules\Hr\Enums\BiometricPunchState;
use Modules\Hr\Enums\BiometricVerifyMethod;
use Modules\Hr\Models\HrBiometricDevice;
use Symfony\Component\HttpFoundation\Response;

final class HikvisionIsapiAdapter implements BiometricDeviceAdapterInterface
{
    private const DEFAULT_PORT = 80;

    public function ping(HrBiometricDevice $device): DeviceHealthResult
    {
        $ip = $device->ip_address;
        $port = $device->port ?? self::DEFAULT_PORT;

        if (empty($ip)) {
            return DeviceHealthResult::failure('Device IP address is not configured.');
        }

        $url = "http://{$ip}:{$port}/ISAPI/System/deviceInfo";
        $startTime = microtime(true);

        try {
            $client = Http::timeout(3);
            if (! empty($device->comm_key)) {
                $client = $client->withDigestAuth('admin', $device->comm_key);
            }

            $response = $client->get($url);
            $latencyMs = (int) round((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                return DeviceHealthResult::success(
                    responseTimeMs: $latencyMs,
                    firmwareVersion: 'Hikvision-ISAPI',
                    deviceTime: CarbonImmutable::now()->toIso8601String(),
                    metadata: ['status_code' => $response->status()],
                );
            }

            return DeviceHealthResult::failure("ISAPI returned HTTP {$response->status()}");
        } catch (\Throwable $e) {
            return DeviceHealthResult::failure("Cannot reach Hikvision device: {$e->getMessage()}");
        }
    }

    public function syncTime(HrBiometricDevice $device, CarbonImmutable $targetTime): bool
    {
        $ip = $device->ip_address;
        $port = $device->port ?? self::DEFAULT_PORT;

        if (empty($ip)) {
            return false;
        }

        $url = "http://{$ip}:{$port}/ISAPI/System/time";
        $xmlPayload = '<Time><timeMode>manual</timeMode><localTime>'.$targetTime->format('Y-m-d\TH:i:s').'</localTime><timeZone>CST-5:30:00</timeZone></Time>';

        try {
            $client = Http::timeout(3)->withHeaders(['Content-Type' => 'application/xml']);
            if (! empty($device->comm_key)) {
                $client = $client->withDigestAuth('admin', $device->comm_key);
            }
            $response = $client->put($url, ['body' => $xmlPayload]);

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    public function fetchLogs(HrBiometricDevice $device, ?string $cursor = null): DeviceLogSyncResult
    {
        $ip = $device->ip_address;
        $port = $device->port ?? self::DEFAULT_PORT;

        if (empty($ip)) {
            return DeviceLogSyncResult::failure('Device IP address is not configured.');
        }

        $url = "http://{$ip}:{$port}/ISAPI/AccessControl/AcsEvent?format=json";
        $position = (int) ($cursor ?? '0');

        $payload = [
            'AcsEventCond' => [
                'searchID' => '1',
                'searchResultPosition' => $position,
                'maxResults' => 50,
                'major' => 5, // Access Control
            ],
        ];

        try {
            $client = Http::timeout(5);
            if (! empty($device->comm_key)) {
                $client = $client->withDigestAuth('admin', $device->comm_key);
            }

            $response = $client->post($url, $payload);
            if (! $response->successful()) {
                return DeviceLogSyncResult::failure("ISAPI event search failed with HTTP {$response->status()}");
            }

            $data = $response->json();
            $events = $data['AcsEvent']['InfoList'] ?? [];
            $punches = [];

            foreach ($events as $event) {
                $pin = (string) ($event['employeeNoString'] ?? $event['cardNo'] ?? '');
                $timeStr = (string) ($event['time'] ?? '');

                if ($pin === '' || $timeStr === '') {
                    continue;
                }

                $punchTime = CarbonImmutable::parse($timeStr)->toDateTimeString();
                $minor = (int) ($event['minor'] ?? 0);

                // In Hikvision: 75 = verified pass
                $verifyMethod = match ($event['currentVerifyMode'] ?? '') {
                    'face' => BiometricVerifyMethod::Face,
                    'fingerprint' => BiometricVerifyMethod::Fingerprint,
                    'card' => BiometricVerifyMethod::Card,
                    default => BiometricVerifyMethod::Unknown,
                };

                $punches[] = new RawBiometricPunchData(
                    biometricUserId: $pin,
                    punchTime: $punchTime,
                    punchState: BiometricPunchState::Auto,
                    verifyMethod: $verifyMethod,
                    cardNumber: isset($event['cardNo']) ? (string) $event['cardNo'] : null,
                    rawPayload: json_encode($event),
                );
            }

            $nextCursor = count($punches) > 0 ? (string) ($position + count($punches)) : $cursor;

            return DeviceLogSyncResult::success($punches, $nextCursor);
        } catch (\Throwable $e) {
            return DeviceLogSyncResult::failure("ISAPI error: {$e->getMessage()}");
        }
    }

    public function clearLogs(HrBiometricDevice $device): bool
    {
        return false; // Hikvision circular buffer clears automatically
    }

    public function parseIncomingPush(HrBiometricDevice $device, Request $request): DeviceLogSyncResult
    {
        $payload = $request->json()->all();
        if (empty($payload)) {
            return DeviceLogSyncResult::success([]);
        }

        $events = isset($payload['events']) ? $payload['events'] : [$payload];
        $punches = [];

        foreach ($events as $event) {
            $pin = (string) ($event['employeeNoString'] ?? $event['cardNo'] ?? $event['user_id'] ?? '');
            $timeStr = (string) ($event['time'] ?? $event['punch_time'] ?? '');

            if ($pin === '' || $timeStr === '') {
                continue;
            }

            try {
                $punchTime = CarbonImmutable::parse($timeStr)->toDateTimeString();
            } catch (\Throwable) {
                continue;
            }

            $punches[] = new RawBiometricPunchData(
                biometricUserId: $pin,
                punchTime: $punchTime,
                punchState: BiometricPunchState::Auto,
                verifyMethod: BiometricVerifyMethod::Face,
                cardNumber: isset($event['cardNo']) ? (string) $event['cardNo'] : null,
                rawPayload: json_encode($event),
            );
        }

        return DeviceLogSyncResult::success($punches);
    }

    public function generatePushResponse(HrBiometricDevice $device, Request $request, DeviceLogSyncResult $syncResult): Response
    {
        return new JsonResponse([
            'statusCode' => 1,
            'statusString' => 'OK',
            'subStatusCode' => 'ok',
            'receivedPunches' => $syncResult->fetchedCount,
        ]);
    }
}
