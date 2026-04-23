<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts;

use Modules\Core\Application\Contracts\ServiceInterface;

/**
 * @method \Modules\HR\Domain\Entities\BiometricDevice execute(array $data = [])
 */
interface CreateBiometricDeviceServiceInterface extends ServiceInterface {}
