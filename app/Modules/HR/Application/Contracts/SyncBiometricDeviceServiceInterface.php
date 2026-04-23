<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts;

use Modules\Core\Application\Contracts\ServiceInterface;

/**
 * @method array<string, mixed> execute(array $data = [])
 */
interface SyncBiometricDeviceServiceInterface extends ServiceInterface {}
