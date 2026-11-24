<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Biometric\Contracts;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Modules\Hr\DTOs\DeviceHealthResult;
use Modules\Hr\DTOs\DeviceLogSyncResult;
use Modules\Hr\Models\HrBiometricDevice;
use Symfony\Component\HttpFoundation\Response;

interface BiometricDeviceAdapterInterface
{
    /**
     * Test connection to the device and retrieve health metrics.
     */
    public function ping(HrBiometricDevice $device): DeviceHealthResult;

    /**
     * Synchronize the hardware RTC time with authoritative server time.
     */
    public function syncTime(HrBiometricDevice $device, CarbonImmutable $targetTime): bool;

    /**
     * Actively pull attendance logs from the device (pull mode).
     */
    public function fetchLogs(HrBiometricDevice $device, ?string $cursor = null): DeviceLogSyncResult;

    /**
     * Clear or mark logs on device memory buffer.
     */
    public function clearLogs(HrBiometricDevice $device): bool;

    /**
     * Parse incoming real-time or batched push payload from the device.
     */
    public function parseIncomingPush(HrBiometricDevice $device, Request $request): DeviceLogSyncResult;

    /**
     * Generate protocol-compliant response to acknowledge received push logs.
     */
    public function generatePushResponse(HrBiometricDevice $device, Request $request, DeviceLogSyncResult $syncResult): Response;
}
