<?php

declare(strict_types=1);

namespace Modules\Product\Interfaces\Http\Controllers;

use App\Shared\Abstractions\BaseController;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Modules\Product\Application\Commands\DeleteProductImageCommand;
use Modules\Product\Application\Commands\SetProductImagesCommand;
use Modules\Product\Application\Commands\UploadProductImageCommand;
use Modules\Product\Application\Services\ProductImageService;
use Modules\Product\Domain\Entities\ProductImage;
use Modules\Product\Interfaces\Http\Requests\SetProductImagesRequest;
use Modules\Product\Interfaces\Http\Requests\UploadProductImageRequest;
use Modules\Product\Interfaces\Http\Resources\ProductImageResource;

class ProductImageController extends BaseController
{
    public function __construct(
        private readonly ProductImageService $productImageService,
    ) {}

    /**
     * List all images for a product, ordered by sort_order.
     */
    public function index(int $productId): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        if (! $this->productImageService->productExists($productId, $tenantId)) {
            return $this->error('Product not found', status: 404);
        }

        $images = $this->productImageService->listImages($productId, $tenantId);

        return $this->success(
            data: array_map(
                fn (ProductImage $img) => (new ProductImageResource($img))->resolve(),
                $images
            ),
            message: 'Product images retrieved successfully',
        );
    }

    /**
     * Replace all images for a product with a set of URL-sourced images
     * (idempotent set operation).
     *
     * For file uploads use POST /products/{productId}/images/upload instead.
     */
    public function store(SetProductImagesRequest $request, int $productId): JsonResponse
    {
        $tenantId = (int) $request->query('tenant_id', '0');

        try {
            $images = $this->productImageService->setImages(new SetProductImagesCommand(
                productId: $productId,
                tenantId: $tenantId,
                images: $request->validated('images'),
            ));

            return $this->success(
                data: array_map(
                    fn (ProductImage $img) => (new ProductImageResource($img))->resolve(),
                    $images
                ),
                message: 'Product images saved successfully',
                status: 201,
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 404);
        }
    }

    /**
     * Upload a single image file for a product and append it to the product's
     * image collection.
     *
     * The file is stored on the private `local` filesystem disk under
     * `tenants/{tenantId}/products/`. Storage is performed in this layer
     * (an infrastructure concern) before the domain service is invoked, keeping
     * the Application layer free of framework storage dependencies.
     * Uploaded files are accessible only via temporary signed URLs.
     */
    public function upload(UploadProductImageRequest $request, int $productId): JsonResponse
    {
        $tenantId = (int) $request->query('tenant_id', '0');

        $storagePath = $request->file('image')->store(
            "tenants/{$tenantId}/products",
            'local'
        );

        try {
            $image = $this->productImageService->uploadImage(new UploadProductImageCommand(
                productId: $productId,
                tenantId: $tenantId,
                storagePath: $storagePath,
                altText: $request->validated('alt_text'),
                sortOrder: (int) ($request->validated('sort_order') ?? 0),
                isPrimary: (bool) ($request->validated('is_primary') ?? false),
            ));

            return $this->success(
                data: (new ProductImageResource($image))->resolve(),
                message: 'Product image uploaded successfully',
                status: 201,
            );
        } catch (DomainException $e) {
            // Clean up the stored file if the domain service rejects the request.
            Storage::disk('local')->delete($storagePath);

            return $this->error($e->getMessage(), status: 404);
        }
    }

    /**
     * Remove a single product image by its ID.
     */
    public function destroy(int $productId, int $imageId): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $this->productImageService->deleteImage(
                new DeleteProductImageCommand($imageId, $productId, $tenantId)
            );

            return $this->success(message: 'Product image deleted successfully');
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 404);
        }
    }
}
