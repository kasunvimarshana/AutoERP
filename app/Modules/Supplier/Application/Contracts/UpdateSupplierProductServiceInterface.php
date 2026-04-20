<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Contracts;

use Modules\Core\Application\Contracts\ServiceInterface;

/**
 * @method \Modules\Supplier\Domain\Entities\SupplierProduct execute(array $data = [])
 */
interface UpdateSupplierProductServiceInterface extends ServiceInterface {}
