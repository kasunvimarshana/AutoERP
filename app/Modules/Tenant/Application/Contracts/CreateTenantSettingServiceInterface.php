<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Contracts;

use Modules\Core\Application\Contracts\ServiceInterface;

/**
 * @method \Modules\Tenant\Domain\Entities\TenantSetting execute(array $data = [])
 */
interface CreateTenantSettingServiceInterface extends ServiceInterface {}
