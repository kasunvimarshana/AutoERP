<?php

declare(strict_types=1);

namespace Modules\Procurement\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Procurement\Application\Commands\UpdateSupplierCommand;
use Modules\Procurement\Domain\Contracts\SupplierRepositoryInterface;
use Modules\Procurement\Domain\Entities\Supplier;

class UpdateSupplierHandler extends BaseHandler
{
    public function __construct(
        private readonly SupplierRepositoryInterface $supplierRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(UpdateSupplierCommand $command): Supplier
    {
        return $this->transaction(function () use ($command): Supplier {
            $existing = $this->supplierRepository->findById($command->id, $command->tenantId);

            if ($existing === null) {
                throw new \DomainException("Supplier with ID {$command->id} not found.");
            }

            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (UpdateSupplierCommand $cmd) use ($existing): Supplier {
                    $updated = new Supplier(
                        id: $existing->id,
                        tenantId: $existing->tenantId,
                        name: $cmd->name,
                        contactName: $cmd->contactName,
                        email: $cmd->email,
                        phone: $cmd->phone,
                        address: $cmd->address,
                        status: $cmd->status,
                        notes: $cmd->notes,
                        createdAt: $existing->createdAt,
                        updatedAt: null,
                    );

                    return $this->supplierRepository->save($updated);
                });
        });
    }
}
