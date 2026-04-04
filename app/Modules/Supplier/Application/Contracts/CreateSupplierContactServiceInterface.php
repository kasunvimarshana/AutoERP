<?php
namespace Modules\Supplier\Application\Contracts;

use Modules\Supplier\Application\DTOs\SupplierContactData;
use Modules\Supplier\Domain\Entities\SupplierContact;

interface CreateSupplierContactServiceInterface
{
    public function execute(SupplierContactData $data): SupplierContact;
}
