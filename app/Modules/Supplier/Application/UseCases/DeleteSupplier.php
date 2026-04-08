<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\UseCases;

use Modules\Supplier\Application\Contracts\DeleteSupplierServiceInterface;

class DeleteSupplier
{
    public function __construct(private readonly DeleteSupplierServiceInterface $service) {}

    public function execute(int $id): bool
    {
        return $this->service->execute(['id' => $id]);
    }
}
