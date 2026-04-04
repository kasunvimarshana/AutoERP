<?php
namespace Modules\Customer\Application\Contracts;

interface DeleteCustomerAddressServiceInterface
{
    public function execute(int $id): bool;
}
