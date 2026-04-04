<?php
namespace Modules\Customer\Application\Contracts;

interface DeleteCustomerServiceInterface
{
    public function execute(int $id): bool;
}
