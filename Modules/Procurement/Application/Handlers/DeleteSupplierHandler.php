<?php

declare(strict_types=1);

namespace Modules\Procurement\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Modules\Procurement\Application\Commands\DeleteSupplierCommand;
use Modules\Procurement\Domain\Contracts\SupplierRepositoryInterface;

class DeleteSupplierHandler extends BaseHandler
{
    public function __construct(
        private readonly SupplierRepositoryInterface $supplierRepository,
    ) {}

    public function handle(DeleteSupplierCommand $command): void
    {
        $this->transaction(function () use ($command): void {
            $supplier = $this->supplierRepository->findById($command->id, $command->tenantId);

            if ($supplier === null) {
                throw new \DomainException('Supplier not found.');
            }

            $this->supplierRepository->delete($command->id, $command->tenantId);
        });
    }
}
