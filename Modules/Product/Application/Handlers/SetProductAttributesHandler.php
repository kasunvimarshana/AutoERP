<?php

declare(strict_types=1);

namespace Modules\Product\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use DomainException;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Product\Application\Commands\SetProductAttributesCommand;
use Modules\Product\Domain\Contracts\ProductAttributeRepositoryInterface;
use Modules\Product\Domain\Contracts\ProductRepositoryInterface;
use Modules\Product\Domain\Entities\ProductAttribute;

class SetProductAttributesHandler extends BaseHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductAttributeRepositoryInterface $productAttributeRepository,
        private readonly Pipeline $pipeline,
    ) {}

    /**
     * Replace all dynamic attributes for a product.
     *
     * @return ProductAttribute[]
     */
    public function handle(SetProductAttributesCommand $command): array
    {
        return $this->transaction(function () use ($command): array {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (SetProductAttributesCommand $cmd): array {
                    $product = $this->productRepository->findById(
                        $cmd->productId,
                        $cmd->tenantId
                    );

                    if ($product === null) {
                        throw new DomainException('Product not found.');
                    }

                    $attributeEntities = array_map(
                        fn (array $raw, int $index) => new ProductAttribute(
                            id: null,
                            productId: $cmd->productId,
                            tenantId: $cmd->tenantId,
                            attributeKey: $raw['attribute_key'],
                            attributeLabel: $raw['attribute_label'],
                            attributeValue: $raw['attribute_value'],
                            attributeType: $raw['attribute_type'] ?? 'text',
                            sortOrder: $raw['sort_order'] ?? $index,
                            createdAt: null,
                            updatedAt: null,
                        ),
                        $cmd->attributes,
                        array_keys($cmd->attributes),
                    );

                    $this->productAttributeRepository->replaceForProduct(
                        $cmd->productId,
                        $cmd->tenantId,
                        $attributeEntities
                    );

                    return $this->productAttributeRepository->findByProduct(
                        $cmd->productId,
                        $cmd->tenantId
                    );
                });
        });
    }
}
