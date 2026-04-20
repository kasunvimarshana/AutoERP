<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Contracts;

use Modules\Core\Application\Contracts\ServiceInterface;

/**
 * @method \Modules\Customer\Domain\Entities\CustomerAddress execute(array $data = [])
 */
interface CreateCustomerAddressServiceInterface extends ServiceInterface {}
