<?php
namespace Modules\Customer\Application\Contracts;

use Modules\Customer\Application\DTOs\CustomerData;
use Modules\Customer\Domain\Entities\Customer;

interface CreateCustomerServiceInterface
{
    public function execute(CustomerData $data): Customer;
}
