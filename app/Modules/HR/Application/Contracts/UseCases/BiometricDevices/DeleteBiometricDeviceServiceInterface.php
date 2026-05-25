<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\BiometricDevices;

use Modules\Core\Application\Results\Result;

interface DeleteBiometricDeviceServiceInterface
{
    public function execute(int|string $id): Result;
}