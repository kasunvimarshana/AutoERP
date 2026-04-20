<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Contracts;

use Modules\Core\Application\Contracts\ServiceInterface;

/**
 * @method \Modules\Pricing\Domain\Entities\CustomerPriceList execute(array $data = [])
 */
interface CreateCustomerPriceListServiceInterface extends ServiceInterface {}
