<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Contracts;

use Modules\Core\Application\Contracts\ServiceInterface;

/**
 * @method \Modules\Pricing\Domain\Entities\PriceListItem execute(array $data = [])
 */
interface UpdatePriceListItemServiceInterface extends ServiceInterface {}
