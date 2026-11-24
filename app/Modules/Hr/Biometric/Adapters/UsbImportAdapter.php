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

final class UsbImportAdapter implements BiometricDeviceAdapterInterface
{
    public function ping(HrBiometricDevice $device): DeviceHealthResult
    {
        return DeviceHealthResult::success(
            responseTimeMs: 0,
            firmwareVersion: 'USB-File-Importer',
            deviceTime: CarbonImmutable::now()->toIso8601String(),
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
        $file = $request->file('file');
        $rawText = $file !== null ? (string) file_get_contents($file->getRealPath()) : (string) $request->getContent();

        if (trim($rawText) === '') {
            return DeviceLogSyncResult::failure('Uploaded file or content is empty.');
        }

        $lines = preg_split("/\r\n|\n|\r/", trim($rawText));
        if ($lines === false || count($lines) === 0) {
            return DeviceLogSyncResult::failure('No valid lines found in file.');
        }

        $punches = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            // Supports ZKTeco attlog.dat (tab separated: PIN \t Time \t State \t Verify) or CSV
            $parts = str_contains($line, "\t") ? explode("\t", $line) : explode(',', $line);
            if (count($parts) < 2) {
                continue;
            }

            $pin = trim($parts[0]);
            $timeStr = trim($parts[1]);

            try {
                $punchTime = CarbonImmutable::parse($timeStr)->toDateTimeString();
            } catch (\Throwable) {
                continue;
            }

            $stateCode = isset($parts[2]) ? (int) trim($parts[2]) : 0;
            $verifyCode = isset($parts[3]) ? (int) trim($parts[3]) : 1;

            $punchState = match ($stateCode) {
                0 => BiometricPunchState::CheckIn,
                1 => BiometricPunchState::CheckOut,
                2 => BiometricPunchState::BreakOut,
                3 => BiometricPunchState::BreakIn,
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
                rawPayload: $line,
            );
        }

        return DeviceLogSyncResult::success($punches);
    }

    public function generatePushResponse(HrBiometricDevice $device, Request $request, DeviceLogSyncResult $syncResult): Response
    {
        return new JsonResponse([
            'success' => $syncResult->isSuccess,
            'imported' => $syncResult->fetchedCount,
        ]);
    }
}
