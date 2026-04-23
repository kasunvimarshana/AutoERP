<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\DeleteBiometricDeviceServiceInterface;
use Modules\HR\Domain\Exceptions\BiometricDeviceNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\BiometricDeviceRepositoryInterface;

class DeleteBiometricDeviceService extends BaseService implements DeleteBiometricDeviceServiceInterface
{
    public function __construct(
        private readonly BiometricDeviceRepositoryInterface $deviceRepository,
    ) {
        parent::__construct($this->deviceRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $device = $this->deviceRepository->find($id);

        if ($device === null) {
            throw new BiometricDeviceNotFoundException($id);
        }

        return $this->deviceRepository->delete($id);
    }
}
