<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Money;
use Modules\Product\Application\Contracts\UpdateProductVariationServiceInterface;
use Modules\Product\Application\DTOs\ProductVariationData;
use Modules\Product\Domain\Entities\ProductVariation;
use Modules\Product\Domain\Events\ProductVariationUpdated;
use Modules\Product\Domain\Exceptions\ProductVariationNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductVariationRepositoryInterface;

class UpdateProductVariationService extends BaseService implements UpdateProductVariationServiceInterface
{
    public function __construct(private readonly ProductVariationRepositoryInterface $variationRepository)
    {
        parent::__construct($variationRepository);
    }

    protected function handle(array $data): ProductVariation
    {
        $id        = $data['id'];
        $variation = $this->variationRepository->find($id);

        if (! $variation) {
            throw new ProductVariationNotFoundException($id);
        }

        $dto   = ProductVariationData::fromArray($data);
        $price = new Money($dto->price, $dto->currency ?? 'USD');

        $variation->updateDetails(
            name:            $dto->name,
            price:           $price,
            attributeValues: $dto->attribute_values ?? $variation->getAttributeValues(),
            status:          $dto->status ?? $variation->getStatus(),
            sortOrder:       $dto->sort_order ?? $variation->getSortOrder(),
            metadata:        $dto->metadata,
        );

        $saved = $this->variationRepository->save($variation);

        $this->addEvent(new ProductVariationUpdated($saved));

        return $saved;
    }
}
