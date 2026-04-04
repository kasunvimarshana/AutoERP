<?php
namespace Modules\Supplier\Application\Contracts;

use Modules\Supplier\Application\DTOs\SupplierData;
use Modules\Supplier\Domain\Entities\Supplier;

interface CreateSupplierServiceInterface
{
    public function execute(SupplierData $data): Supplier;
}
