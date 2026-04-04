<?php
namespace Modules\Customer\Application\Contracts;

use Modules\Customer\Application\DTOs\CustomerData;
use Modules\Customer\Domain\Entities\Customer;

interface UpdateCustomerServiceInterface
{
    public function execute(int $id, CustomerData $data): Customer;
}
