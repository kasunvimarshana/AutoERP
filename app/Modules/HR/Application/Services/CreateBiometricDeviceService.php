<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\HR\Application\Contracts\CreateBiometricDeviceServiceInterface;
use Modules\HR\Application\DTOs\BiometricDeviceData;
use Modules\HR\Domain\Entities\BiometricDevice;
use Modules\HR\Domain\RepositoryInterfaces\BiometricDeviceRepositoryInterface;
use Modules\HR\Domain\ValueObjects\BiometricDeviceStatus;

class CreateBiometricDeviceService extends BaseService implements CreateBiometricDeviceServiceInterface
{
    public function __construct(
        private readonly BiometricDeviceRepositoryInterface $deviceRepository,
    ) {
        parent::__construct($this->deviceRepository);
    }

    protected function handle(array $data): BiometricDevice
    {
        $dto = BiometricDeviceData::fromArray($data);

        $existing = $this->deviceRepository->findByTenantAndCode($dto->tenantId, $dto->code);
        if ($existing !== null) {
            throw new DomainException("Biometric device code '{$dto->code}' already exists for this tenant.");
        }

        $now = new \DateTimeImmutable;
        $device = new BiometricDevice(
            tenantId: $dto->tenantId,
            name: $dto->name,
            code: $dto->code,
            deviceType: $dto->deviceType,
            ipAddress: $dto->ipAddress,
            port: $dto->port,
            location: $dto->location,
            orgUnitId: $dto->orgUnitId,
            status: BiometricDeviceStatus::from($dto->status),
            metadata: $dto->metadata,
            createdAt: $now,
            updatedAt: $now,
        );

        return $this->deviceRepository->save($device);
    }
}
