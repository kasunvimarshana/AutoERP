<?php
namespace Modules\Customer\Application\Contracts;

use Modules\Customer\Application\DTOs\CustomerAddressData;
use Modules\Customer\Domain\Entities\CustomerAddress;

interface CreateCustomerAddressServiceInterface
{
    public function execute(CustomerAddressData $data): CustomerAddress;
}
