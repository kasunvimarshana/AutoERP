<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Biometric\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Hr\Biometric\Services\BiometricDeviceService;
use Modules\Hr\Models\HrBiometricDevice;

final class SyncBiometricDeviceJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly int $deviceId,
    ) {}

    public function handle(BiometricDeviceService $deviceService): void
    {
        $device = HrBiometricDevice::find($this->deviceId);

        if ($device === null || ! $device->is_enabled) {
            return;
        }

        try {
            $result = $deviceService->syncDeviceLogs($device);
            if (! $result->isSuccess) {
                Log::warning("Biometric device sync failed for [{$device->name}]: {$result->errorMessage}");
            }
        } catch (\Throwable $e) {
            Log::error("Exception syncing biometric device [{$device->name}]: {$e->getMessage()}");
            throw $e;
        }
    }
}
