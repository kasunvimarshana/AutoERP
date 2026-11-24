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

final class DahuaCgiAdapter implements BiometricDeviceAdapterInterface
{
    private const DEFAULT_PORT = 80;

    public function ping(HrBiometricDevice $device): DeviceHealthResult
    {
        $ip = $device->ip_address;
        $port = $device->port ?? self::DEFAULT_PORT;

        if (empty($ip)) {
            return DeviceHealthResult::failure('Device IP address is not configured.');
        }

        $url = "http://{$ip}:{$port}/cgi-bin/magicBox.cgi?action=getSystemInfo";
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
                    firmwareVersion: 'Dahua-CGI',
                    deviceTime: CarbonImmutable::now()->toIso8601String(),
                );
            }

            return DeviceHealthResult::failure("Dahua CGI returned HTTP {$response->status()}");
        } catch (\Throwable $e) {
            return DeviceHealthResult::failure("Cannot reach Dahua device: {$e->getMessage()}");
        }
    }

    public function syncTime(HrBiometricDevice $device, CarbonImmutable $targetTime): bool
    {
        $ip = $device->ip_address;
        $port = $device->port ?? self::DEFAULT_PORT;

        if (empty($ip)) {
            return false;
        }

        $formattedTime = urlencode($targetTime->format('Y-m-d H:i:s'));
        $url = "http://{$ip}:{$port}/cgi-bin/global.cgi?action=setCurrentTime&time={$formattedTime}";

        try {
            $client = Http::timeout(3);
            if (! empty($device->comm_key)) {
                $client = $client->withDigestAuth('admin', $device->comm_key);
            }
            $response = $client->get($url);

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

        $url = "http://{$ip}:{$port}/cgi-bin/recordFinder.cgi?action=find&name=AccessControlCardRec&count=50";

        try {
            $client = Http::timeout(5);
            if (! empty($device->comm_key)) {
                $client = $client->withDigestAuth('admin', $device->comm_key);
            }

            $response = $client->get($url);
            if (! $response->successful()) {
                return DeviceLogSyncResult::failure("Dahua recordFinder failed with HTTP {$response->status()}");
            }

            $body = $response->body();
            $punches = $this->parseDahuaRecords($body);

            return DeviceLogSyncResult::success($punches);
        } catch (\Throwable $e) {
            return DeviceLogSyncResult::failure("Dahua fetch error: {$e->getMessage()}");
        }
    }

    public function clearLogs(HrBiometricDevice $device): bool
    {
        return false;
    }

    public function parseIncomingPush(HrBiometricDevice $device, Request $request): DeviceLogSyncResult
    {
        return DeviceLogSyncResult::success([]);
    }

    public function generatePushResponse(HrBiometricDevice $device, Request $request, DeviceLogSyncResult $syncResult): Response
    {
        return new JsonResponse(['status' => 'OK']);
    }

    /**
     * @return list<RawBiometricPunchData>
     */
    private function parseDahuaRecords(string $body): array
    {
        $lines = explode("\n", $body);
        $records = [];
        $currentRecord = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/records\[(\d+)\]\.(\w+)=(.*)/', $line, $matches)) {
                $idx = (int) $matches[1];
                $key = $matches[2];
                $val = trim($matches[3]);
                $records[$idx][$key] = $val;
            }
        }

        $punches = [];
        foreach ($records as $item) {
            $pin = $item['UserID'] ?? $item['CardNo'] ?? '';
            $createTime = $item['CreateTime'] ?? '';

            if ($pin === '' || $createTime === '') {
                continue;
            }

            try {
                $punchTime = CarbonImmutable::createFromTimestamp((int) $createTime)->toDateTimeString();
            } catch (\Throwable) {
                continue;
            }

            $punches[] = new RawBiometricPunchData(
                biometricUserId: $pin,
                punchTime: $punchTime,
                punchState: BiometricPunchState::Auto,
                verifyMethod: BiometricVerifyMethod::Card,
                cardNumber: $item['CardNo'] ?? null,
                rawPayload: json_encode($item),
            );
        }

        return $punches;
    }
}
