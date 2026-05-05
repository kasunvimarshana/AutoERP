<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\DeleteProductVariantServiceInterface;
use Modules\Product\Application\Contracts\RefreshProductSearchProjectionServiceInterface;
use Modules\Product\Domain\Exceptions\ProductVariantNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductVariantRepositoryInterface;

class DeleteProductVariantService extends BaseService implements DeleteProductVariantServiceInterface
{
    public function __construct(
        private readonly ProductVariantRepositoryInterface $productVariantRepository,
        private readonly RefreshProductSearchProjectionServiceInterface $refreshProjectionService,
    )
    {
        parent::__construct($productVariantRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $productVariant = $this->productVariantRepository->find($id);

        if (! $productVariant) {
            throw new ProductVariantNotFoundException($id);
        }

        $deleted = $this->productVariantRepository->delete($id);
        $tenantId = $productVariant->getTenantId();

        if ($deleted && $tenantId !== null) {
            $this->refreshProjectionService->execute($tenantId, $productVariant->getProductId());
        }

        return $deleted;
    }
}
