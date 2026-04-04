<?php
namespace Modules\Customer\Application\Contracts;

use Modules\Customer\Application\DTOs\CustomerAddressData;
use Modules\Customer\Domain\Entities\CustomerAddress;

interface UpdateCustomerAddressServiceInterface
{
    public function execute(int $id, CustomerAddressData $data): CustomerAddress;
}
