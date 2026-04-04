<?php
namespace Modules\Supplier\Application\Contracts;

interface DeleteSupplierContactServiceInterface
{
    public function execute(int $id): bool;
}
