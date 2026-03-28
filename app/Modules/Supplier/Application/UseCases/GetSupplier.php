<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\UseCases;

use Modules\Supplier\Application\Contracts\CreateSupplierServiceInterface;
use Modules\Supplier\Domain\Entities\Supplier;

class GetSupplier
{
    public function __construct(private readonly CreateSupplierServiceInterface $service) {}

    public function execute(int $id): ?Supplier
    {
        return $this->service->find($id);
    }
}
