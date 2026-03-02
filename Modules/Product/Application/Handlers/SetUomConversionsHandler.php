<?php

declare(strict_types=1);

namespace Modules\Product\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use DomainException;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Product\Application\Commands\SetUomConversionsCommand;
use Modules\Product\Domain\Contracts\ProductRepositoryInterface;
use Modules\Product\Domain\Contracts\UomConversionRepositoryInterface;
use Modules\Product\Domain\Entities\UomConversion;

class SetUomConversionsHandler extends BaseHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly UomConversionRepositoryInterface $uomConversionRepository,
        private readonly Pipeline $pipeline,
    ) {}

    /**
     * Replace all UOM conversions for a product.
     *
     * @return UomConversion[]
     */
    public function handle(SetUomConversionsCommand $command): array
    {
        return $this->transaction(function () use ($command): array {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (SetUomConversionsCommand $cmd): array {
                    $product = $this->productRepository->findById(
                        $cmd->productId,
                        $cmd->tenantId
                    );

                    if ($product === null) {
                        throw new DomainException('Product not found.');
                    }

                    $conversionEntities = array_map(
                        fn (array $raw) => new UomConversion(
                            id: null,
                            productId: $cmd->productId,
                            tenantId: $cmd->tenantId,
                            fromUom: $raw['from_uom'],
                            toUom: $raw['to_uom'],
                            factor: (string) $raw['factor'],
                            createdAt: null,
                            updatedAt: null,
                        ),
                        $cmd->conversions,
                    );

                    $this->uomConversionRepository->replaceForProduct(
                        $cmd->productId,
                        $cmd->tenantId,
                        $conversionEntities
                    );

                    return $this->uomConversionRepository->findByProduct(
                        $cmd->productId,
                        $cmd->tenantId
                    );
                });
        });
    }
}
