<?php

declare(strict_types=1);

namespace Modules\Product\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use DomainException;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Product\Application\Commands\UploadProductImageCommand;
use Modules\Product\Domain\Contracts\ProductImageRepositoryInterface;
use Modules\Product\Domain\Contracts\ProductRepositoryInterface;
use Modules\Product\Domain\Entities\ProductImage;
use Modules\Product\Domain\Enums\ProductImageSourceType;

/**
 * Validates that the target product exists and appends a single uploaded
 * image record to its image collection.
 *
 * File storage is intentionally handled in the HTTP layer (controller) before
 * this handler is invoked, keeping the Application layer free of framework
 * storage concerns. The `storagePath` on the command is the path returned by
 * Laravel's `Storage::put()` / `UploadedFile::store()`.
 */
class UploadProductImageHandler extends BaseHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductImageRepositoryInterface $productImageRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(UploadProductImageCommand $command): ProductImage
    {
        return $this->transaction(function () use ($command): ProductImage {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (UploadProductImageCommand $cmd): ProductImage {
                    $product = $this->productRepository->findById(
                        $cmd->productId,
                        $cmd->tenantId
                    );

                    if ($product === null) {
                        throw new DomainException('Product not found.');
                    }

                    $image = new ProductImage(
                        id: null,
                        productId: $cmd->productId,
                        tenantId: $cmd->tenantId,
                        imagePath: $cmd->storagePath,
                        altText: $cmd->altText,
                        sortOrder: $cmd->sortOrder,
                        isPrimary: $cmd->isPrimary,
                        imageSourceType: ProductImageSourceType::Upload->value,
                    );

                    return $this->productImageRepository->save($image);
                });
        });
    }
}
