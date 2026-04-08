<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\DeleteProductVariationServiceInterface;
use Modules\Product\Domain\Events\ProductVariationDeleted;
use Modules\Product\Domain\Exceptions\ProductVariationNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductVariationRepositoryInterface;

class DeleteProductVariationService extends BaseService implements DeleteProductVariationServiceInterface
{
    public function __construct(private readonly ProductVariationRepositoryInterface $variationRepository)
    {
        parent::__construct($variationRepository);
    }

    protected function handle(array $data): bool
    {
        $id        = $data['id'];
        $variation = $this->variationRepository->find($id);

        if (! $variation) {
            throw new ProductVariationNotFoundException($id);
        }

        $tenantId = $variation->getTenantId();

        $this->variationRepository->delete($id);

        $this->addEvent(new ProductVariationDeleted($id, $tenantId));

        return true;
    }
}
