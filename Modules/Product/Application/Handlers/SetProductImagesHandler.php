<?php

declare(strict_types=1);

namespace Modules\Product\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use DomainException;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Product\Application\Commands\SetProductImagesCommand;
use Modules\Product\Domain\Contracts\ProductImageRepositoryInterface;
use Modules\Product\Domain\Contracts\ProductRepositoryInterface;
use Modules\Product\Domain\Entities\ProductImage;
use Modules\Product\Domain\Enums\ProductImageSourceType;

class SetProductImagesHandler extends BaseHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductImageRepositoryInterface $productImageRepository,
        private readonly Pipeline $pipeline,
    ) {}

    /**
     * Replace all images for a product.
     *
     * @return ProductImage[]
     */
    public function handle(SetProductImagesCommand $command): array
    {
        return $this->transaction(function () use ($command): array {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (SetProductImagesCommand $cmd): array {
                    $product = $this->productRepository->findById(
                        $cmd->productId,
                        $cmd->tenantId
                    );

                    if ($product === null) {
                        throw new DomainException('Product not found.');
                    }

                    $imageEntities = array_map(
                        fn (array $raw, int $index) => new ProductImage(
                            id: null,
                            productId: $cmd->productId,
                            tenantId: $cmd->tenantId,
                            imagePath: $raw['image_path'],
                            altText: $raw['alt_text'] ?? null,
                            sortOrder: $raw['sort_order'] ?? $index,
                            isPrimary: (bool) ($raw['is_primary'] ?? false),
                            imageSourceType: ProductImageSourceType::Url->value,
                        ),
                        $cmd->images,
                        array_keys($cmd->images),
                    );

                    $this->productImageRepository->replaceForProduct(
                        $cmd->productId,
                        $cmd->tenantId,
                        $imageEntities
                    );

                    return $this->productImageRepository->findByProduct(
                        $cmd->productId,
                        $cmd->tenantId
                    );
                });
        });
    }
}
