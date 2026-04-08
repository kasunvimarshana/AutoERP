<?php

declare(strict_types=1);

namespace Modules\Customer\Domain\Events;

use Modules\Customer\Domain\Entities\Customer;

class CustomerCreated
{
    public function __construct(public readonly Customer $customer) {}
}
