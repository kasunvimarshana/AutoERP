<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Contracts;

use Modules\Core\Application\Contracts\ServiceInterface;

/**
 * @method \Modules\Customer\Domain\Entities\Customer execute(array $data = [])
 */
interface CreateCustomerServiceInterface extends ServiceInterface {}
