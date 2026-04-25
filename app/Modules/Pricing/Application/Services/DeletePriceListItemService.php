<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Pricing\Application\Contracts\DeletePriceListItemServiceInterface;
use Modules\Pricing\Domain\Exceptions\PriceListItemNotFoundException;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListItemRepositoryInterface;

class DeletePriceListItemService extends BaseService implements DeletePriceListItemServiceInterface
{
    public function __construct(private readonly PriceListItemRepositoryInterface $priceListItemRepository)
    {
        parent::__construct($priceListItemRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $item = $this->priceListItemRepository->find($id);

        if (! $item) {
            throw new PriceListItemNotFoundException($id);
        }

        return $this->priceListItemRepository->delete($id);
    }
}
