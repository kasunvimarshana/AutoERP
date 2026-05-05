<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts;

use Modules\Core\Application\Contracts\ReadServiceInterface;

/** @method \Modules\Finance\Domain\Entities\Payment|null find(mixed $id) */
interface FindPaymentServiceInterface extends ReadServiceInterface {}
