<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\BiometricDevices;

use Modules\Core\Application\Results\Result;

interface CreateBiometricDeviceServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}