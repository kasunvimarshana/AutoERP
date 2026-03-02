<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Application\Handlers;

use Modules\Manufacturing\Application\Commands\CreateBomCommand;
use Modules\Manufacturing\Domain\Contracts\ManufacturingRepositoryInterface;
use Modules\Manufacturing\Domain\Entities\Bom;

class CreateBomHandler
{
    public function __construct(
        private readonly ManufacturingRepositoryInterface $repository,
    ) {}

    public function handle(CreateBomCommand $command): Bom
    {
        if (empty($command->lines)) {
            throw new \DomainException('A Bill of Materials must have at least one component line.');
        }

        if (bccomp($command->outputQuantity, '0', 4) <= 0) {
            throw new \DomainException('Output quantity must be greater than zero.');
        }

        return $this->repository->createBom([
            'tenant_id'       => $command->tenantId,
            'product_id'      => $command->productId,
            'variant_id'      => $command->variantId,
            'output_quantity' => $command->outputQuantity,
            'reference'       => $command->reference,
            'is_active'       => true,
            'lines'           => $command->lines,
            'created_by'      => $command->createdBy,
        ]);
    }
}
