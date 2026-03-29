<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Money;
use Modules\Product\Application\Contracts\CreateComboItemServiceInterface;
use Modules\Product\Application\DTOs\ComboItemData;
use Modules\Product\Domain\Entities\ComboItem;
use Modules\Product\Domain\Events\ComboItemCreated;
use Modules\Product\Domain\Exceptions\ProductNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ComboItemRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;

class CreateComboItemService extends BaseService implements CreateComboItemServiceInterface
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ComboItemRepositoryInterface $comboItemRepository,
    ) {
        parent::__construct($comboItemRepository);
    }

    protected function handle(array $data): ComboItem
    {
        $dto = ComboItemData::fromArray($data);

        $product = $this->productRepository->find($dto->product_id);
        if (! $product) {
            throw new ProductNotFoundException($dto->product_id);
        }

        $priceOverride = null;
        if (isset($dto->price_override) && $dto->price_override !== null) {
            $priceOverride = new Money((float) $dto->price_override, $dto->currency ?? 'USD');
        }

        $comboItem = new ComboItem(
            productId:          $dto->product_id,
            tenantId:           $dto->tenant_id,
            componentProductId: $dto->component_product_id,
            quantity:           $dto->quantity,
            priceOverride:      $priceOverride,
            sortOrder:          $dto->sort_order ?? 0,
            metadata:           $dto->metadata,
        );

        $saved = $this->comboItemRepository->save($comboItem);

        $this->addEvent(new ComboItemCreated($saved));

        return $saved;
    }
}
