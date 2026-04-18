<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\DeleteProductIdentifierServiceInterface;
use Modules\Product\Domain\Exceptions\ProductIdentifierNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductIdentifierRepositoryInterface;

class DeleteProductIdentifierService extends BaseService implements DeleteProductIdentifierServiceInterface
{
    public function __construct(private readonly ProductIdentifierRepositoryInterface $productIdentifierRepository)
    {
        parent::__construct($productIdentifierRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $productIdentifier = $this->productIdentifierRepository->find($id);

        if (! $productIdentifier) {
            throw new ProductIdentifierNotFoundException($id);
        }

        return $this->productIdentifierRepository->delete($id);
    }
}
