<?php

declare(strict_types=1);

namespace Modules\Product\Application\Contracts;

use Modules\Core\Application\Contracts\ServiceInterface;

/** @method \Modules\Product\Domain\Entities\Serial execute(array $data = []) */
interface CreateSerialServiceInterface extends ServiceInterface {}
