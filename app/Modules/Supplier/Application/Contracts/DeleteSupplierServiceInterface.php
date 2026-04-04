<?php
namespace Modules\Supplier\Application\Contracts;

interface DeleteSupplierServiceInterface
{
    public function execute(int $id): bool;
}
