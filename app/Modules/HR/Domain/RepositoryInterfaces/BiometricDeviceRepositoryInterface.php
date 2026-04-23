<?php

declare(strict_types=1);

namespace Modules\HR\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\HR\Domain\Entities\BiometricDevice;

interface BiometricDeviceRepositoryInterface extends RepositoryInterface
{
    public function save(BiometricDevice $device): BiometricDevice;

    public function find(int|string $id, array $columns = ['*']): ?BiometricDevice;

    public function findByTenantAndCode(int $tenantId, string $code): ?BiometricDevice;
}
