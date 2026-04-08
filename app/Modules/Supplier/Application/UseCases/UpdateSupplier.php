<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\UseCases;

use Modules\Supplier\Application\Contracts\UpdateSupplierServiceInterface;
use Modules\Supplier\Domain\Entities\Supplier;

class UpdateSupplier
{
    public function __construct(private readonly UpdateSupplierServiceInterface $service) {}

    public function execute(array $data): Supplier
    {
        return $this->service->execute($data);
    }
}
