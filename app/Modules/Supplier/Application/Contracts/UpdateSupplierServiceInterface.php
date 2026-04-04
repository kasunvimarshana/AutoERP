<?php
namespace Modules\Supplier\Application\Contracts;

use Modules\Supplier\Application\DTOs\SupplierData;
use Modules\Supplier\Domain\Entities\Supplier;

interface UpdateSupplierServiceInterface
{
    public function execute(int $id, SupplierData $data): Supplier;
}
