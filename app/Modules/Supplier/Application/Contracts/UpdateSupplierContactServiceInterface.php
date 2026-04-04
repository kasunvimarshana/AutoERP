<?php
namespace Modules\Supplier\Application\Contracts;

use Modules\Supplier\Application\DTOs\SupplierContactData;
use Modules\Supplier\Domain\Entities\SupplierContact;

interface UpdateSupplierContactServiceInterface
{
    public function execute(int $id, SupplierContactData $data): SupplierContact;
}
