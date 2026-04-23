<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\CreateAttendanceLogServiceInterface;
use Modules\HR\Application\Contracts\SyncBiometricDeviceServiceInterface;
use Modules\HR\Application\DTOs\SyncBiometricDeviceData;
use Modules\HR\Domain\Contracts\BiometricDeviceAdapterInterface;
use Modules\HR\Domain\Exceptions\BiometricDeviceNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\BiometricDeviceRepositoryInterface;

class SyncBiometricDeviceService extends BaseService implements SyncBiometricDeviceServiceInterface
{
    public function __construct(
        private readonly BiometricDeviceRepositoryInterface $deviceRepository,
        private readonly BiometricDeviceAdapterInterface $adapter,
        private readonly CreateAttendanceLogServiceInterface $createLogService,
    ) {
        parent::__construct($this->deviceRepository);
    }

    /** @return array<string, mixed> */
    protected function handle(array $data): array
    {
        $dto = SyncBiometricDeviceData::fromArray($data);
        $device = $this->deviceRepository->find($dto->deviceId);

        if ($device === null) {
            throw new BiometricDeviceNotFoundException($dto->deviceId);
        }

        $since = $dto->syncFrom !== null
            ? new \DateTimeImmutable($dto->syncFrom)
            : new \DateTimeImmutable('-24 hours');

        $rawLogs = $this->adapter->syncAttendanceLogs($device, $since);

        $created = 0;
        $failed = 0;
        $errors = [];

        foreach ($rawLogs as $rawLog) {
            try {
                $this->createLogService->execute([
                    'tenant_id' => $dto->tenantId,
                    'employee_id' => $rawLog['employee_id'],
                    'biometric_device_id' => $device->getId(),
                    'punch_time' => $rawLog['punch_time'],
                    'punch_type' => $rawLog['punch_type'] ?? 'check_in',
                    'source' => 'biometric',
                    'raw_data' => $rawLog,
                ]);
                $created++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = $e->getMessage();
            }
        }

        return [
            'device_id' => $device->getId(),
            'total' => count($rawLogs),
            'created' => $created,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }
}
