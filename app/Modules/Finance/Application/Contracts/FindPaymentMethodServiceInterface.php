<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts;

use Modules\Core\Application\Contracts\ServiceInterface;

/** @method \Modules\Finance\Domain\Entities\PaymentMethod|null find(mixed $id) */
interface FindPaymentMethodServiceInterface extends ServiceInterface {}
