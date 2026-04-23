<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\UpdateBiometricDeviceServiceInterface;
use Modules\HR\Application\DTOs\BiometricDeviceData;
use Modules\HR\Domain\Entities\BiometricDevice;
use Modules\HR\Domain\Exceptions\BiometricDeviceNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\BiometricDeviceRepositoryInterface;
use Modules\HR\Domain\ValueObjects\BiometricDeviceStatus;

class UpdateBiometricDeviceService extends BaseService implements UpdateBiometricDeviceServiceInterface
{
    public function __construct(
        private readonly BiometricDeviceRepositoryInterface $deviceRepository,
    ) {
        parent::__construct($this->deviceRepository);
    }

    protected function handle(array $data): BiometricDevice
    {
        $id = (int) ($data['id'] ?? 0);
        $device = $this->deviceRepository->find($id);

        if ($device === null) {
            throw new BiometricDeviceNotFoundException($id);
        }

        $dto = BiometricDeviceData::fromArray($data);

        if ($device->getTenantId() !== $dto->tenantId) {
            throw new BiometricDeviceNotFoundException($id);
        }

        $device->update(
            name: $dto->name,
            code: $dto->code,
            deviceType: $dto->deviceType,
            ipAddress: $dto->ipAddress,
            port: $dto->port,
            location: $dto->location,
            orgUnitId: $dto->orgUnitId,
            status: BiometricDeviceStatus::from($dto->status),
            metadata: $dto->metadata,
        );

        return $this->deviceRepository->save($device);
    }
}
