<?php

declare(strict_types=1);

namespace Modules\Procurement\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Procurement\Application\Commands\CreateSupplierCommand;
use Modules\Procurement\Domain\Contracts\SupplierRepositoryInterface;
use Modules\Procurement\Domain\Entities\Supplier;
use Modules\Procurement\Domain\Enums\SupplierStatus;

class CreateSupplierHandler extends BaseHandler
{
    public function __construct(
        private readonly SupplierRepositoryInterface $supplierRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(CreateSupplierCommand $command): Supplier
    {
        return $this->transaction(function () use ($command): Supplier {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (CreateSupplierCommand $cmd): Supplier {
                    $supplier = new Supplier(
                        id: null,
                        tenantId: $cmd->tenantId,
                        name: $cmd->name,
                        contactName: $cmd->contactName,
                        email: $cmd->email,
                        phone: $cmd->phone,
                        address: $cmd->address,
                        status: SupplierStatus::Active->value,
                        notes: $cmd->notes,
                        createdAt: null,
                        updatedAt: null,
                    );

                    return $this->supplierRepository->save($supplier);
                });
        });
    }
}
